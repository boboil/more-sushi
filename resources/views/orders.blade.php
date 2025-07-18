@extends('layouts.app')

@section('content')

    <div class="container mt-4">
        <h2>Данные заказа</h2>
        @foreach($data as $order)
            <div class="card mb-4">
                <div class="card-header">
                    Заказ №{{ $order['incoming_order_id'] }}
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                        @foreach($order as $key => $value)
                            @if($key === 'products')
                                <tr>
                                    <th colspan="2">Продукты</th>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <table class="table table-sm table-striped mb-0">
                                            <thead>
                                            <tr>
                                                @foreach(reset($value) as $pkey => $pval)
                                                    <th>{{ $pkey }}</th>
                                                @endforeach
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($value as $product)
                                                <tr>
                                                    @foreach($product as $pval)
                                                        <td>{{ $pval }}</td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            @else
                                <tr>
                                    <th>{{ $key }}</th>
                                    <td>{{ $value }}</td>
                                </tr>
                            @endif
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
@endsection
