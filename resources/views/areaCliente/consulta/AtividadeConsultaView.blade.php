@extends('areaCliente.viewBase')

@section('title', 'NAJ - Cliente | Atividades')

@section('css')
    <link rel="stylesheet" href="{{ env('APP_URL') }}css/gijgo.min.css">
    <style>
        button {
            background: #fff !important;
        }
        .naj-datatable i {
            color: rgba(47, 50, 62, .75);
            cursor: pointer;
            font-size: 14px;
        }
    </style>
@endsection

@section('active-layer', 'processo')
@section('content')

<div id="datatable-atividades" class="naj-datatable" style="height: 91%;"></div>
<div class="row datatable-body mt-0 row-bottom-atividade-financeiro">
    <div class="col-6 p-0">
        <span>
            <span class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="Total de Horas do período selecionado."></span>
            <b>Total de Horas:</b> <span id='total_horas' style="margin-top: 1px; position: absolute;"></span>&emsp;
        </span>
    </div>
</div>

@component('areaCliente.componentes.modalConsultaAnexoAtividade')
@endcomponent

@component('areaCliente.componentes.modalConsultaObservacao')
@endcomponent

@endsection

@section('scripts')
    <script src="{{ env('APP_URL') }}js/gijgo.min.js"></script>
    <script src="{{ env('APP_URL') }}js/messages.pt-br.js"></script>
    <script src="{{ env('APP_URL') }}js/tables/anexoAtividadeTable.js"></script>
    <script src="{{ env('APP_URL') }}js/tables/atividadeTable.js"></script>
    <script src="{{ env('APP_URL') }}js/atividades.js"></script>
@endsection