class AndamentoProcessoTable extends Table {

    constructor(codigo_processo) {
        super();
        
        this.target           = 'datatable-andamento-processo';
        this.name             = 'Andamentos do Processo';
        this.route            = 'processos/andamento';
        this.key              = ['ID'];
        this.openLoaded       = true;
        this.isItEditable     = false;
        this.isItDestructible = false;;
        this.showTitle        = false;
        this.defaultFilters   = false;

        // campos
        this.addField({
            name: 'DATA',
            title: 'Data Andamento',
            width: 15,
            onLoad: (data, row) =>  {
                const data_atual = getDataAtual();
                let mesAtual           = data_atual.split('-')[1] - 1;
                let mesInicio          = row.DATA.split('/')[1] - 1;
                let data_atual_moment  = moment([data_atual.split('-')[0], mesAtual, data_atual.split('-')[2]]);
                let data_inicio_moment = moment([row.DATA.split('/')[2], mesInicio, row.DATA.split('/')[0]]);
                
                const days_difference = data_atual_moment.diff(data_inicio_moment, 'days');

                if(days_difference > 30) 
                    return `
                    <table style="margin: 5px 0 0 20px;">
                        <tr>
                            <td>${row.DATA}</td>
                        </tr>
                    </table>
                `;

                let string_days = 'Hoje';
                if(days_difference > 0) {
                    string_days = `Há ${days_difference} dias`;
                }

                return `
                    <table style="margin: 5px 0 0 20px;">
                        <tr>
                            <td>${row.DATA}</td>
                        </tr>
                        <tr>
                            <td><span class="mt-1 mb-2 badge badge-warning badge-rounded badge-informacoes-processo">${string_days}</span></td>
                        </tr>
                    </table>
                `;
            }
        });

        this.addField({
            name: 'DESCRICAO_ANDAMENTO',
            title: 'Descrição no Tribunal',
            width: 50,
            onLoad: (data, row) =>  {
                return `
                    <span style="word-break: break-word;">${row.DESCRICAO_ANDAMENTO}</span>
                `;
            }
        });

        this.addField({
            name: 'TRADUCAO_ANDAMENTO',
            title: 'Descrição Simplificada',
            width: 35,
            onLoad: (data, row) =>  {
                if(row.TRADUCAO_ANDAMENTO != null) {
                    return row.TRADUCAO_ANDAMENTO;
                }

                return `-`;
            }
        });

        this.addFixedFilter('CODIGO_PROCESSO', 'I', codigo_processo);
    }

    makeSkeleton() {
        const actionsIn = this.getActionsIn();
        const actionsOut = this.getActionsOut();
        let defaultFilters = '';

        if(this.defaultFilters){
            defaultFilters = `<div style="display: flex;">
                        <div style="display: flex; align-items: center;">
                            <span>Pesquisar em&nbsp;</span>
                        </div>
                        <select id="${this.ids.filterColumn}">
                            ${this.getFilterColumns()}
                        </select>
                        <div style="display: flex; align-items: center;">
                            <span>&nbsp;que&nbsp;</span>&nbsp;
                        </div>
                        <select id="${this.ids.filterOption}"></select>&nbsp;

                        <div id="${this.ids.filterValueContainer}" style="display: flex;"></div>

                        <div id="${this.ids.filterValue2Container}" style="display: flex;">
                            <div style="display: flex; align-items: center;">&nbsp;&nbsp;e&nbsp;&nbsp;</div>
                            <input id="${this.ids.filterValue2}" type="text" />
                        </div>

                        &nbsp;&nbsp;

                        <button id=${this.ids.filterButton} title="Adicionar filtro" class="btn btnCustom" style="background: #2f323e !important;">
                            <i class="fas fa-plus btn-icon"></i>
                        </button>

                        <div style="display: flex; align-items: center;">
                            <div class="y-separator"></div>
                        </div>

                        <button id="${this.ids.searchButton}" class="btn btnCustom" style="background: #2f323e !important;">
                            <i class="fas fa-search btn-icon"></i>&nbsp;&nbsp;
                            Pesquisar
                        </button>
                    </div>

                            <div class="data-table-filter-list" id="${this.ids.filterList}"></div>`;
        }

        fillById(this.target, `
            ${this.showTitle && `
                <div class="page-breadcrumb border-bottom">
                    <div class="row">
                        <div class="col-lg-3 col-md-4 col-xs-12 align-self-center">
                            <h5 class="font-medium text-uppercase mb-0">${this.name}</h5>
                </div>
                        <div class="col-lg-9 col-md-8 col-xs-12 align-self-center">
                            <nav aria-label="breadcrumb" class="mt-2 float-md-right float-left">
                                <ol class="breadcrumb mb-0 justify-content-end p-0">
                                    <li class="breadcrumb-item">&nbsp;</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            ` || ''}

            <div class="datatable-body" id="${this.ids.container}">
                <div id="${this.ids.loading}" class="loader loader-default" data-half></div>

                <div class="data-table-filter">
                    
                    ${defaultFilters}
                    
                </div>

                <div class="data-table-footer">
                    <div>
                        <div class="actions-container" act="1">
                            <div class="actions-in" act="1">
                                <button id="${this.ids.actionsInButton}" class="btn btnCustom action-in-button btn-action-default" act="1">
                                    <i class="fas fa-ellipsis-v btn-icon" act="1"></i>
                                </button>
                                <ul act="1" id="list-actions-default" class="actions-in-list" style="display: none;">${actionsIn}</ul>
                            </div>
                            <div class="actions-out">
                                ${actionsOut}
                            </div>

                            <div class="y-separator"></div>
                        </div>

                        <div class="pagination-container">
                            <button id="${this.ids.fisrtPage}" title="Primeira página" class="btn btnCustom action-in-button">
                                <i class="fas fa-caret-left fa-2x btn-icon"></i>
                            </button>

                            <button id="${this.ids.previousPage}" title="Página anterior" class="btn btnCustom action-in-button">
                                <i class="fas fa-angle-left fa-2x btn-icon"></i>
                            </button>

                            <div id="${this.ids.paginationPages}"></div>

                            <button id="${this.ids.nextPage}" title="Próxima página" class="btn btnCustom action-in-button">
                                <i class="fas fa-angle-right fa-2x btn-icon"></i>
                            </button>

                            <button id="${this.ids.lastPage}" title="Última página" class="btn btnCustom action-in-button">
                                <i class="fas fa-caret-right fa-2x btn-icon"></i>
                            </button>
                        </div>

                        <div class="y-separator"></div>

                        <div class="pages-container">
                            Página&nbsp;
                            <span id="${this.ids.currentPage}">1</span>
                            &nbsp; de &nbsp;
                            <span id="${this.ids.totalPages}">1</span>
                        </div>
                    </div>
                    <div>
                        Exibir
                        &nbsp;
                        <select id="${this.ids.perPage}">
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100" selected>100</option>
                        </select>
                        &nbsp;
                        registros de
                        &nbsp;
                        <span id="${this.ids.total}">0</span>
                        &nbsp;&nbsp;
                    </div>
                </div>

                <div class="data-table-header">
                    ${this.getHeaders()}
                </div>
                <div class="data-table-content naj-scrollable" id="${this.id}">
                    <div id="${this.ids.notSearch}" class="not-search">Nenhuma busca realizada</div>
                </div>
            </div>
        `);

        this.verifyHasAction(actionsIn, actionsOut);

        if(this.defaultFilters){
            this.loadFilterOptions();
            this.loadFilterValue();
            this.verifyBetweenFilter();

            byId(this.id).style.overflow = 'overlay';
        }
        
        this.notifyActions();
        
        if(this.defaultFilters){

            addEventById(this.ids.filterColumn, 'change', () => this.onChangeFilterColumn());
            addEventById(this.ids.filterOption, 'change', () => this.onChangeFilterOption());
            addEventById(this.ids.filterButton, 'click', () => {
                this.applyMasksEvents();

                this.addValidateFilter();
            });
        
            addEventById(this.ids.searchButton, 'click', () => this.onSearch());
            addEventById(this.ids.container, 'click', e => e.target.getAttribute('act') != 1 && this.closeActions());
            addEventById(this.ids.actionsInButton, 'click', () => {
                const list = document.querySelector(`#${this.ids.container} .actions-in-list`);

                list.classList.toggle('action-in-open');
            });
        } else if(this.forceButtonsInTreePoints) {
            addEventById(this.ids.actionsInButton, 'click', () => {
                const list = document.querySelector(`#${this.ids.container} .actions-in-list`);
    
                list.classList.toggle('action-in-open');
            });
        }
    }

}