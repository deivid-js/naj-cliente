$(document).ready(function() {

    let buttonVoltar = `
        <button type="button" id="voltar" class="btn button-voltar-auto-cadastro" title="Cancelar" onclick="onClickVoltarLogin();">
            Cancelar
        </button>
    `;
    
    $('.footer-steps-naj').append(buttonVoltar);
});

function onChangeCpf() {
    $('#login').val($('#cpf').val());
}

function onChangeNome() {
    let nome = $('input[name=nome]').val().split(' ');

    $('[name=apelido]').val(nome[0]);
}

function validaCampoLogin() {
    let login = $('[name=login]').val(),
        cpf   = $('[name=cpf]').val();

    if(login == cpf) {
        return true;
    }

    return false;
}

function validaCampoEmail() {
    let usuario = $('[name=email_recuperacao]').val().substring(0, $('[name=email_recuperacao]').val().indexOf("@")),
        dominio = $('[name=email_recuperacao]').val().substring($('[name=email_recuperacao]').val().indexOf("@") + 1, $('[name=email_recuperacao]').val().length);

    if ((usuario.length >=1) && (dominio.length >=3) && (usuario.search("@")==-1) && (dominio.search("@")==-1) 
            && (usuario.search(" ")==-1) && (dominio.search(" ")==-1) && (dominio.search(".")!=-1) && (dominio.indexOf(".") >=1)
            && (dominio.lastIndexOf(".") < dominio.length - 1))
    {
        return true;
    }

    return false;
}

function onClickVoltarLogin() {
    return window.location.href = `${baseURL}auth/login`;
}