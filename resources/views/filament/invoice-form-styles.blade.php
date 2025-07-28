@extends('filament-panels::page')

@section('header')
<style>
    .fi-fo-field-wrp .fi-input-wrp .fi-select-input .fi-select-search-input {
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 8px;
    }

    .fi-modal .fi-modal-content {
        border-radius: 12px;
    }

    .customer-create-hint {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border: 1px solid #bae6fd;
        border-radius: 8px;
        padding: 12px;
        margin-top: 8px;
    }

    .service-create-hint {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        border: 1px solid #bbf7d0;
        border-radius: 8px;
        padding: 12px;
        margin-top: 8px;
    }

    .quick-add-button {
        transition: all 0.2s ease;
    }

    .quick-add-button:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .fi-select-option-create {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border: 1px dashed #f59e0b;
        border-radius: 6px;
        margin: 4px;
        padding: 8px 12px;
        color: #92400e;
        font-weight: 500;
    }

    .fi-select-option-create:hover {
        background: linear-gradient(135deg, #fde68a 0%, #fcd34d 100%);
        border-color: #d97706;
    }
</style>
@endsection