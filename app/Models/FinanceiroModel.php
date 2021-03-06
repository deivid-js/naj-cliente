<?php

namespace App\Models;

use App\Models\NajModel;
use Illuminate\Support\Facades\DB;
use App\Models\PessoaRelacionamentoUsuarioModel;

/**
 * Modelo do financeiro.
 *
 * @since 2020-08-10
 */
class FinanceiroModel extends NajModel {

    protected function loadTable() {
        $codigoCliente = implode(',', $this->getRelacionamentoClientes());

        if($codigoCliente == "") {
            $codigoCliente = "-1";
        }

        $this->setTable('conta');

        $this->addColumn('CODIGO', true)->setHidden();

        $this->setOrder('CP.SITUACAO, CP.DATA_VENCIMENTO, CP.CODIGO_CONTA, CP.PARCELA ASC');

        $this->addAllColumns();

        $this->addRawFilter("CP.SITUACAO IN('A','P')");
        $this->addRawFilter("CONTA.CODIGO_PESSOA IN ({$codigoCliente})");
        $this->addRawFilter("((CONTA.TIPO = 'R' AND CONTA.PAGADOR = '2') OR CONTA.TIPO = 'P')");
        $this->addRawFilter("(N.TIPO_SUB NOT IN ('M', 'J', 'C') OR N.TIPO_SUB IS NULL)");        
        $this->addRawFilter("CONTA.DISPONIVEL_CLIENTE = 'S'");

        $this->setRawBaseSelect("
                SELECT [COLUMNS]
                  FROM CONTA
            INNER JOIN CONTA_PARCELA CP
                    ON CP.CODIGO_CONTA = CONTA.CODIGO
            INNER JOIN NATUREZA_FINANCEIRA N 
                    ON N.CODIGO = CONTA.CODIGO_NATUREZA
             LEFT JOIN PRC PC
                    ON PC.CODIGO = CONTA.CODIGO_PROCESSO
             LEFT JOIN PESSOA P1
                    ON P1.CODIGO = CONTA.CODIGO_PESSOA
             LEFT JOIN PESSOA P2
                    ON P2.CODIGO = CONTA.CODIGO_ADVERSARIO
             LEFT JOIN PESSOA P3
                    ON P3.CODIGO = PC.CODIGO_ADVERSARIO
             LEFT JOIN PRC_CLASSE CL 
                    ON CL.CODIGO = PC.CODIGO_CLASSE
             LEFT JOIN PRC_CARTORIO CA 
                    ON CA.CODIGO = PC.CODIGO_CARTORIO
             LEFT JOIN PRC_COMARCA CO 
                    ON CO.CODIGO = PC.CODIGO_COMARCA
        ");
    }

    public function getRelacionamentoClientes() {
        $codigo_usuario = request()->get('filterUser');

        if($codigo_usuario) {
           $codigo_usuario = json_decode(base64_decode($codigo_usuario));
        } else {
           return [];
        }

        $PessoaRelUsuarioModel = new PessoaRelacionamentoUsuarioModel();
        $relacionamentos       = $PessoaRelUsuarioModel->getRelacionamentosUsuarioModuloFinanceiroContasReceber($codigo_usuario[0]->val);
        $aCodigo = [];

        foreach($relacionamentos as $relacionamento) {
           $aCodigo[] = $relacionamento->pessoa_codigo;
        }

        $rota = request()->route()->getName();

        //Se for paginate remove o filtro para não add duas vezes
        if($rota == 'financeiro.receber.paginate') {
           request()->request->remove('filterUser');
        }

        return $aCodigo;
    }

    private function getRelacionamentoClientesPagar() {
        $codigo_usuario = request()->get('filterUser');

        if($codigo_usuario) {
           $codigo_usuario = json_decode(base64_decode($codigo_usuario));
        } else {
           return [];
        }

        $PessoaRelUsuarioModel = new PessoaRelacionamentoUsuarioModel();
        $relacionamentos       = $PessoaRelUsuarioModel->getRelacionamentosUsuarioModuloFinanceiroContasPagar($codigo_usuario[0]->val);
        $aCodigo = [];

        foreach($relacionamentos as $relacionamento) {
           $aCodigo[] = $relacionamento->pessoa_codigo;
        }

        $rota = request()->route()->getName();

        //Se for paginate remove o filtro para não add duas vezes
        if($rota == 'financeiro.receber.paginate') {
           request()->request->remove('filterUser');
        }

        return $aCodigo;
    }

    public function addAllColumns() {
        $this->addRawColumn("CONTA.CODIGO AS CODIGO_CONTA")
            ->addRawColumn("CONTA.TIPO AS TIPO_CONTA")
            ->addRawColumn("CP.ID AS ID_PARCELA")
            ->addRawColumn("CP.SITUACAO")
            ->addRawColumn("CP.PARCELA AS PARCELA_ATUAL")
            ->addRawColumn("(
                SELECT COUNT(0)
                  FROM CONTA_PARCELA
                 WHERE CODIGO_CONTA = CONTA.CODIGO
            ) AS PARCELA_TOTAL")
            ->addRawColumn("DATE_FORMAT(CP.DATA_VENCIMENTO, '%d/%m/%Y') AS DATA_VENCIMENTO")
            ->addRawColumn("DATE_FORMAT(CP.DATA_PAGAMENTO, '%d/%m/%Y') AS DATA_PAGAMENTO")
            ->addRawColumn("IF (
                CP.VALOR_PARCIAL > 0,
                CP.VALOR_PARCELA - CP.VALOR_PARCIAL,
                CP.VALOR_PARCELA
            ) AS VALOR_PARCELA")
            ->addRawColumn("IF (
                (
                    SELECT SUM(VALOR_PAGAMENTO)
                      FROM CONTA_PARCELA_PARCIAL
                     WHERE ID_PARCELA = CP.ID
                ) > 0, 'SIM', 'NÃO'
            ) AS PAGAMENTOS_PARCIAIS")
            ->addRawColumn("IF (
                CP.DATA_PAGAMENTO IS NOT NULL,
                CP.VALOR_PAGAMENTO, (
                    SELECT SUM(VALOR_PAGAMENTO)
                      FROM CONTA_PARCELA_PARCIAL
                     WHERE ID_PARCELA = CP.ID
                )
            ) AS VALOR_PAGAMENTO")
            ->addRawColumn("P1.NOME AS NOME_CLIENTE")
            ->addRawColumn("IF (
                CONTA.CODIGO_ADVERSARIO IS NOT NULL,
                P2.NOME,
                P3.NOME
            ) AS NOME_ADVERSARIO")
            ->addRawColumn("CONTA.DESCRICAO")
            ->addRawColumn("PC.NUMERO_PROCESSO_NEW")
            ->addRawColumn("PC.NUMERO_PROCESSO")
            ->addRawColumn("CL.CLASSE")
            ->addRawColumn("CO.COMARCA")
            ->addRawColumn("CO.UF")
            ->addRawColumn("CA.CARTORIO");
    }

    protected function handleCustomFilter($filter) {
      switch (strtolower($filter->col)) {
            case 'cp.data_vencimento':
                $this->addRawFilter("(
                  CP.DATA_VENCIMENTO BETWEEN '{$filter->val}' AND '{$filter->val2}'
                  OR 
                  CP.DATA_PAGAMENTO BETWEEN '{$filter->val}' AND '{$filter->val2}'
                )");
                break;
            default:
                $this->throwException('Filtro customizado não tratado');
                break;
        }

        return false;
    }

    public function getTotalPagarTotalReceber() {
        $codigoClientePagar = implode(',', $this->getRelacionamentoClientesPagar());

        if($codigoClientePagar == "") {
            $codigoClientePagar = "-1";
        }

        $codigoClienteReceber = implode(',', $this->getRelacionamentoClientes());

        if($codigoClienteReceber == "") {
            $codigoClienteReceber = "-1";
        }

        $total_recebido = DB::select("
                SELECT (
                         sum(VALOR_PARCIAL) +
                         sum(VALOR_PAGAMENTO) 
                       ) AS TOTAL_PAGO            
                  FROM CONTA C
            INNER JOIN CONTA_PARCELA CP 
                    ON CP.CODIGO_CONTA = C.CODIGO
            INNER JOIN NATUREZA_FINANCEIRA N
                    ON N.CODIGO = C.CODIGO_NATUREZA
                 WHERE CP.SITUACAO IN('A','P')
                   AND C.CODIGO_PESSOA IN ({$codigoClienteReceber})
                   AND (N.TIPO_SUB NOT IN('M','J','C') OR N.TIPO_SUB IS NULL)
                   AND C.DISPONIVEL_CLIENTE = 'S'
                   #PARA CONTAS DA GUIA 'A RECEBER' (QUE O CLIENTE TEM PARA RECEBER)
                   AND (
                        (C.TIPO='R' AND C.PAGADOR='2') OR C.TIPO='P'
                       )
        ");

        $total_receber = DB::select("
                SELECT IF(sum(VALOR_PARCELA-VALOR_PARCIAL) IS NULL, 0.00, sum(VALOR_PARCELA-VALOR_PARCIAL)) AS TOTAL_EM_ABERTO
                  FROM CONTA C
            INNER JOIN CONTA_PARCELA CP
                    ON CP.CODIGO_CONTA = C.CODIGO
            INNER JOIN NATUREZA_FINANCEIRA N
                    ON N.CODIGO = C.CODIGO_NATUREZA
                 WHERE CP.SITUACAO IN('A')
                   AND C.CODIGO_PESSOA IN ({$codigoClienteReceber})
                   AND (N.TIPO_SUB NOT IN('M','J','C') OR N.TIPO_SUB IS NULL)
                   AND C.DISPONIVEL_CLIENTE = 'S'
                   #PARA CONTAS DA GUIA 'A RECEBER' (QUE O CLIENTE TEM PARA RECEBER)
                   AND (
                        (C.TIPO='R' AND C.PAGADOR='2') OR C.TIPO='P'
                       )
        ");

        $total_pagar = DB::select("
                SELECT IF(sum(VALOR_PARCELA-VALOR_PARCIAL) IS NULL,0.00,sum(VALOR_PARCELA-VALOR_PARCIAL)) AS TOTAL_EM_ABERTO
                  FROM CONTA C
            INNER JOIN CONTA_PARCELA CP 
                    ON CP.CODIGO_CONTA = C.CODIGO
            INNER JOIN NATUREZA_FINANCEIRA N
                    ON N.CODIGO = C.CODIGO_NATUREZA
                 WHERE CP.SITUACAO IN('A')
                   AND C.CODIGO_PESSOA IN ({$codigoClientePagar})
                   AND (N.TIPO_SUB NOT IN('M','J','C') OR N.TIPO_SUB IS NULL)
                   AND C.DISPONIVEL_CLIENTE = 'S'
                   #PARA CONTAS DA GUIA 'A PAGAR' (QUE O CLIENTE TEM PARA PAGAR PARA O ESCRITÓRIO)
                   AND (
                        C.TIPO='R' AND (C.PAGADOR='1' or C.PAGADOR is null)
                       )
        ");

        $total_pago = DB::select("
                SELECT (
                         sum(VALOR_PARCIAL) +
                         sum(VALOR_PAGAMENTO) 
                       ) AS TOTAL_PAGO            
                  FROM CONTA C
            INNER JOIN CONTA_PARCELA CP 
                    ON CP.CODIGO_CONTA = C.CODIGO
            INNER JOIN NATUREZA_FINANCEIRA N
                    ON N.CODIGO = C.CODIGO_NATUREZA
                 WHERE CP.SITUACAO IN('A','P')
                   AND C.CODIGO_PESSOA IN ({$codigoClientePagar})
                   AND (N.TIPO_SUB NOT IN('M','J','C') OR N.TIPO_SUB IS NULL)
                   AND C.DISPONIVEL_CLIENTE = 'S'
                   #PARA CONTAS DA GUIA 'A PAGAR' (QUE O CLIENTE TEM PARA PAGAR PARA O ESCRITÓRIO)
                   AND (
                        C.TIPO='R' AND (C.PAGADOR='1' or C.PAGADOR is null)
                       )
        ");

        $total_pagar_atrasado = DB::select("
			SELECT IF(sum(VALOR_PARCELA-VALOR_PARCIAL) IS NULL,0.00,sum(VALOR_PARCELA-VALOR_PARCIAL)) AS TOTAL_PAGAR_ATRASADO
			  FROM CONTA C
		INNER JOIN CONTA_PARCELA CP
		    	ON CP.CODIGO_CONTA = C.CODIGO
		INNER JOIN NATUREZA_FINANCEIRA N
				ON N.CODIGO = C.CODIGO_NATUREZA
		     WHERE CP.SITUACAO IN('A')
			   AND C.CODIGO_PESSOA IN ({$codigoClientePagar})
			   AND data_vencimento < DATE_FORMAT(now(),'%Y-%m-%d')
                           AND C.DISPONIVEL_CLIENTE = 'S'
			   AND (N.TIPO_SUB NOT IN('M','J','C') OR N.TIPO_SUB IS NULL)
			   #PARA CONTAS DA GUIA 'A PAGAR' (QUE O CLIENTE TEM PARA PAGAR PARA O ESCRITÓRIO)
			   AND (
				     C.TIPO='R' AND (C.PAGADOR='1' or C.PAGADOR is null)
				   )
        ");

        $total_receber_atrasado = DB::select("
                SELECT IF(sum(VALOR_PARCELA-VALOR_PARCIAL) IS NULL,0.00,sum(VALOR_PARCELA-VALOR_PARCIAL)) AS TOTAL_RECEBER_ATRASADO            
                  FROM CONTA C
            INNER JOIN CONTA_PARCELA CP 
                    ON CP.CODIGO_CONTA = C.CODIGO
            INNER JOIN NATUREZA_FINANCEIRA N
                    ON N.CODIGO = C.CODIGO_NATUREZA
                 WHERE CP.SITUACAO IN('A')
                   AND C.CODIGO_PESSOA IN ({$codigoClienteReceber})
                   AND data_vencimento < DATE_FORMAT(now(),'%Y-%m-%d')
                   AND (N.TIPO_SUB NOT IN('M','J','C') OR N.TIPO_SUB IS NULL)
                   AND C.DISPONIVEL_CLIENTE = 'S'
                   #PARA CONTAS DA GUIA 'A RECEBER' (QUE O CLIENTE TEM PARA RECEBER)
                   AND (
                         (C.TIPO='R' AND C.PAGADOR='2') OR C.TIPO='P'
                        )
        ");

        return [
			'total_pagar' => $total_pagar,
			'total_pago' => $total_pago,
			'total_receber' => $total_receber,
			'total_recebido' => $total_recebido,
			'total_pagar_atrasado' => $total_pagar_atrasado,
			'total_receber_atrasado' => $total_receber_atrasado
		];
    }

    public function getTotalRecebidoReceberAtrasado($parametro) {
        $codigoCliente = implode(',', $this->getRelacionamentoClientes());

        if($codigoCliente == "") {
            $codigoCliente = "-1";
        }

        $total_recebido = DB::select("
                SELECT (
                         sum(VALOR_PARCIAL) +
                         sum(VALOR_PAGAMENTO) 
                       )AS TOTAL_PAGO            
                  FROM CONTA C
            INNER JOIN CONTA_PARCELA CP 
                    ON CP.CODIGO_CONTA = C.CODIGO
            INNER JOIN NATUREZA_FINANCEIRA N
                    ON N.CODIGO = C.CODIGO_NATUREZA
                 WHERE CP.SITUACAO IN('A','P')
                   AND C.CODIGO_PESSOA IN ({$codigoCliente})
                   AND (N.TIPO_SUB NOT IN('M','J','C') OR N.TIPO_SUB IS NULL)
                   AND C.DISPONIVEL_CLIENTE = 'S'
                   #PARA CONTAS DA GUIA 'A RECEBER' (QUE O CLIENTE TEM PARA RECEBER)
                   AND (
                        (C.TIPO='R' AND C.PAGADOR='2') OR C.TIPO='P'
                       )            
                   #FILTRO POR DATA AQUI
                   AND (
                         CP.DATA_VENCIMENTO BETWEEN '{$parametro->data_inicial}' and '{$parametro->data_final}'
                         OR
                         CP.DATA_PAGAMENTO BETWEEN '{$parametro->data_inicial}' and '{$parametro->data_final}'
                       )
        ");

        $total_aberto = DB::select("
                SELECT IF(sum(VALOR_PARCELA-VALOR_PARCIAL) IS NULL, 0.00, sum(VALOR_PARCELA-VALOR_PARCIAL)) AS TOTAL_EM_ABERTO
                  FROM CONTA C
            INNER JOIN CONTA_PARCELA CP
                    ON CP.CODIGO_CONTA = C.CODIGO
            INNER JOIN NATUREZA_FINANCEIRA N
                    ON N.CODIGO = C.CODIGO_NATUREZA
                 WHERE CP.SITUACAO IN('A')
                   AND C.CODIGO_PESSOA IN ({$codigoCliente})
                   AND (N.TIPO_SUB NOT IN('M','J','C') OR N.TIPO_SUB IS NULL)
                   AND C.DISPONIVEL_CLIENTE = 'S'
                   #PARA CONTAS DA GUIA 'A RECEBER' (QUE O CLIENTE TEM PARA RECEBER)
                   AND (
                        (C.TIPO='R' AND C.PAGADOR='2') OR C.TIPO='P'
                        )
                   #FILTRO POR DATA AQUI
                   AND (
						 CP.DATA_VENCIMENTO BETWEEN '{$parametro->data_inicial}' and '{$parametro->data_final}'
                         #CP.DATA_VENCIMENTO BETWEEN '{$parametro->data_inicial}' and '{$parametro->data_final}'
                         #OR
                         #CP.DATA_PAGAMENTO BETWEEN '{$parametro->data_inicial}' and '{$parametro->data_final}'
						 #era como estava acima mas foi trocado por que tava dando pau em cliente
                       )
        ");

        $total_atrasado = DB::select("
                SELECT IF(sum(VALOR_PARCELA-VALOR_PARCIAL) IS NULL,0.00,sum(VALOR_PARCELA-VALOR_PARCIAL)) AS TOTAL_ATRASADO            
                  FROM CONTA C
            INNER JOIN CONTA_PARCELA CP 
                    ON CP.CODIGO_CONTA = C.CODIGO
            INNER JOIN NATUREZA_FINANCEIRA N
                    ON N.CODIGO = C.CODIGO_NATUREZA
                 WHERE CP.SITUACAO IN('A')
                   AND C.CODIGO_PESSOA IN ({$codigoCliente})
                   AND data_vencimento < DATE_FORMAT(now(),'%Y-%m-%d')
                   AND (N.TIPO_SUB NOT IN('M','J','C') OR N.TIPO_SUB IS NULL)
                   AND C.DISPONIVEL_CLIENTE = 'S'
                   #PARA CONTAS DA GUIA 'A RECEBER' (QUE O CLIENTE TEM PARA RECEBER)
                   AND (
                          (C.TIPO='R' AND C.PAGADOR='2') OR C.TIPO='P'
                        )
                   #FILTRO POR DATA AQUI
                   AND (
						CP.DATA_VENCIMENTO BETWEEN '{$parametro->data_inicial}' and '{$parametro->data_final}'                         
						AND
						CP.DATA_VENCIMENTO < DATE_FORMAT(now(),'%Y-%m-%d')
						#CP.DATA_PAGAMENTO BETWEEN '{$parametro->data_inicial}' and '{$parametro->data_final}'
						#era como estava acima mas foi trocado por que tava dando pau em cliente
                       )
        ");

        return ['total_atrasado' => $total_atrasado[0], 'total_recebido' => $total_recebido[0], 'total_aberto' => $total_aberto[0]];
    }

}