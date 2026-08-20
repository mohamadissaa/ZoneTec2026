<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html
    lang="{{ app()->getLocale() }}"
    dir="{{ core()->getCurrentLocale()->direction }}"
>
    <head>
        <meta
            http-equiv="Cache-control"
            content="no-cache"
        >

        <meta
            http-equiv="Content-Type"
            content="text/html; charset=utf-8"
        />

        @php
            $fontPath = [];

            if (app()->getLocale() == 'en' && $orderCurrencyCode == 'INR') {
                $fontFamily = [
                    'regular' => 'DejaVu Sans',
                    'bold'    => 'DejaVu Sans',
                ];
            }  else {
                $fontFamily = [
                    'regular' => 'Arial, sans-serif',
                    'bold'    => 'Arial, sans-serif',
                ];
            }

            if (in_array(app()->getLocale(), ['ar', 'he', 'fa', 'tr', 'ru', 'uk'])) {
                $fontFamily = [
                    'regular' => 'DejaVu Sans',
                    'bold'    => 'DejaVu Sans',
                ];
            } elseif (app()->getLocale() == 'zh_CN') {
                $fontPath = [
                    'regular' => asset('fonts/NotoSansSC-Regular.ttf'),
                    'bold'    => asset('fonts/NotoSansSC-Bold.ttf'),
                ];

                $fontFamily = [
                    'regular' => 'Noto Sans SC',
                    'bold'    => 'Noto Sans SC Bold',
                ];
            } elseif (app()->getLocale() == 'ja') {
                $fontPath = [
                    'regular' => asset('fonts/NotoSansJP-Regular.ttf'),
                    'bold'    => asset('fonts/NotoSansJP-Bold.ttf'),
                ];

                $fontFamily = [
                    'regular' => 'Noto Sans JP',
                    'bold'    => 'Noto Sans JP Bold',
                ];
            } elseif (app()->getLocale() == 'hi_IN') {
                $fontPath = [
                    'regular' => asset('fonts/Hind-Regular.ttf'),
                    'bold'    => asset('fonts/Hind-Bold.ttf'),
                ];

                $fontFamily = [
                    'regular' => 'Hind',
                    'bold'    => 'Hind Bold',
                ];
            } elseif (app()->getLocale() == 'bn') {
                $fontPath = [
                    'regular' => asset('fonts/NotoSansBengali-Regular.ttf'),
                    'bold'    => asset('fonts/NotoSansBengali-Bold.ttf'),
                ];

                $fontFamily = [
                    'regular' => 'Noto Sans Bengali',
                    'bold'    => 'Noto Sans Bengali Bold',
                ];
            } elseif (app()->getLocale() == 'sin') {
                $fontPath = [
                    'regular' => asset('fonts/NotoSansSinhala-Regular.ttf'),
                    'bold'    => asset('fonts/NotoSansSinhala-Bold.ttf'),
                ];

                $fontFamily = [
                    'regular' => 'Noto Sans Sinhala',
                    'bold'    => 'Noto Sans Sinhala Bold',
                ];
            }
        @endphp

        <!-- lang supports inclusion -->
        <style type="text/css">
            @if (! empty($fontPath['regular']))
                @font-face {
                    src: url({{ $fontPath['regular'] }}) format('truetype');
                    font-family: {{ $fontFamily['regular'] }};
                }
            @endif

            @if (! empty($fontPath['bold']))
                @font-face {
                    src: url({{ $fontPath['bold'] }}) format('truetype');
                    font-family: {{ $fontFamily['bold'] }};
                    font-style: bold;
                }
            @endif

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: {{ $fontFamily['regular'] }};
            }

            body {
                font-size: 10px;
                color: #091341;
                font-family: "{{ $fontFamily['regular'] }}";
            }

            b, th {
                font-family: "{{ $fontFamily['bold'] }}";
            }

            .page-content {
                padding: 12px;
            }

            .page-header {
                border-bottom: 1px solid #E9EFFC;
                text-align: center;
                font-size: 24px;
                text-transform: uppercase;
                color: #000DBB;
                padding: 24px 0;
                margin: 0;
            }

            .logo-container {
                position: absolute;
                top: 20px;
                left: 20px;
            }

            .logo-container.rtl {
                left: auto;
                right: 20px;
            }

            .logo-container img {
                width: 130px;
                height: auto;
            }

            .page-header b {
                display: inline-block;
                vertical-align: middle;
            }

            .small-text {
                font-size: 7px;
            }

            table {
                width: 100%;
                border-spacing: 1px 0;
                border-collapse: separate;
                margin-bottom: 16px;
            }

            table thead th {
                background-color: #E9EFFC;
                color: #000DBB;
                padding: 6px 18px;
                text-align: left;
            }

            table.rtl thead tr th {
                text-align: right;
            }

            table tbody td {
                padding: 9px 18px;
                border-bottom: 1px solid #E9EFFC;
                text-align: left;
                vertical-align: top;
            }

            table.rtl tbody tr td {
                text-align: right;
            }

            .summary {
                width: 100%;
                display: inline-block;
            }

            .summary table {
                float: right;
                width: 250px;
                padding-top: 5px;
                padding-bottom: 5px;
                background-color: #E9EFFC;
                white-space: nowrap;
            }

            .summary table.rtl {
                width: 280px;
            }

            .summary table.rtl {
                margin-right: 480px;
            }

            .summary table td {
                padding: 5px 10px;
            }

            .summary table td:nth-child(2) {
                text-align: center;
            }

            .summary table td:nth-child(3) {
                text-align: right;
            }
        </style>
    </head>

    <body dir="{{ core()->getCurrentLocale()->direction }}">
        <div class="logo-container {{ core()->getCurrentLocale()->direction }}">
            @if (core()->getConfigData('sales.invoice_settings.pdf_print_outs.logo'))
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(Storage::url(core()->getConfigData('sales.invoice_settings.pdf_print_outs.logo')))) }}"/>
            @else
                <img src="data:image/png;base64,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"/>
            @endif
        </div>

        <div class="page">
            <!-- Header -->
            <div class="page-header">
                <b>@lang('shop::app.customers.account.orders.invoice-pdf.invoice')</b>
            </div>

            <div class="page-content">
                <!-- Invoice Information -->
                <table class="{{ core()->getCurrentLocale()->direction }}">
                    <tbody>
                        <tr>
                            @if (core()->getConfigData('sales.invoice_settings.pdf_print_outs.invoice_id'))
                                <td style="width: 50%; padding: 2px 18px;border:none;">
                                    <b>
                                        @lang('shop::app.customers.account.orders.invoice-pdf.invoice-id'):
                                    </b>

                                    <span>
                                        #{{ $invoice->increment_id ?? $invoice->id }}
                                    </span>
                                </td>
                            @endif

                            @if (core()->getConfigData('sales.invoice_settings.pdf_print_outs.order_id'))
                                <td style="width: 50%; padding: 2px 18px;border:none;">
                                    <b>
                                        @lang('shop::app.customers.account.orders.invoice-pdf.order-id'):
                                    </b>

                                    <span>
                                        #{{ $invoice->order->increment_id }}
                                    </span>
                                </td>
                            @endif
                        </tr>

                        <tr>
                            <td style="width: 50%; padding: 2px 18px;border:none;">
                                <b>
                                    @lang('shop::app.customers.account.orders.invoice-pdf.date'):
                                </b>

                                <span>
                                    {{ core()->formatDate($invoice->created_at, 'd-m-Y') }}
                                </span>
                            </td>

                            <td style="width: 50%; padding: 2px 18px;border:none;">
                                <b>
                                    @lang('shop::app.customers.account.orders.invoice-pdf.order-date'):
                                </b>

                                <span>
                                    {{ core()->formatDate($invoice->order->created_at, 'd-m-Y') }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Invoice Information -->
                <table class="{{ core()->getCurrentLocale()->direction }}">
                    <tbody>
                        <tr>
                            @if (! empty(core()->getConfigData('sales.shipping.origin.country')))
                                <td style="width: 50%; padding: 2px 18px;border:none;">
                                    <b style="display: inline-block; margin-bottom: 4px;">
                                        {{ core()->getConfigData('sales.shipping.origin.store_name') }}
                                    </b>

                                    <div>
                                        <div>{{ core()->getConfigData('sales.shipping.origin.address') }}</div>

                                        <div>{{ core()->getConfigData('sales.shipping.origin.zipcode') . ' ' . core()->getConfigData('sales.shipping.origin.city') }}</div>

                                        <div>{{ core()->getConfigData('sales.shipping.origin.state') . ', ' . core()->getConfigData('sales.shipping.origin.country') }}</div>
                                    </div>
                                </td>
                            @endif

                            <td style="width: 50%; padding: 2px 18px;border:none;">
                                @if ($invoice->hasPaymentTerm())
                                    <div style="margin-bottom: 12px">
                                        <b style="display: inline-block; margin-bottom: 4px;">
                                            @lang('shop::app.customers.account.orders.invoice-pdf.payment-terms'):
                                        </b>

                                        <span>
                                            {{ $invoice->getFormattedPaymentTerm() }}
                                        </span>
                                    </div>
                                @endif

                                @if (core()->getConfigData('sales.shipping.origin.bank_details'))
                                    <div>
                                        <b style="display: inline-block; margin-bottom: 4px;">
                                            @lang('shop::app.customers.account.orders.invoice-pdf.bank-details'):
                                        </b>

                                        <div>
                                            {!! nl2br(core()->getConfigData('sales.shipping.origin.bank_details')) !!}
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Billing & Shipping Address -->
                <table class="{{ core()->getCurrentLocale()->direction }}">
                    <thead>
                        <tr>
                            @if ($invoice->order->billing_address)
                                <th style="width: 50%;">
                                    <b>
                                        @lang('shop::app.customers.account.orders.invoice-pdf.bill-to')
                                    </b>
                                </th>
                            @endif

                            @if ($invoice->order->shipping_address)
                                <th style="width: 50%">
                                    <b>
                                        @lang('shop::app.customers.account.orders.invoice-pdf.ship-to')
                                    </b>
                                </th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            @if ($invoice->order->billing_address)
                                <td style="width: 50%">
                                    <div>{{ $invoice->order->billing_address->company_name ?? '' }}<div>

                                    <div>{{ $invoice->order->billing_address->name }}</div>

                                    <div>{{ $invoice->order->billing_address->address }}</div>

                                    <div>{{ $invoice->order->billing_address->postcode . ' ' . $invoice->order->billing_address->city }}</div>

                                    <div>{{ $invoice->order->billing_address->state . ', ' . core()->country_name($invoice->order->billing_address->country) }}</div>

                                    <div>@lang('shop::app.customers.account.orders.invoice-pdf.contact'): {{ $invoice->order->billing_address->phone }}</div>
                                </td>
                            @endif

                            @if ($invoice->order->shipping_address)
                                <td style="width: 50%">
                                    <div>{{ $invoice->order->shipping_address->company_name ?? '' }}<div>

                                    <div>{{ $invoice->order->shipping_address->name }}</div>

                                    <div>{{ $invoice->order->shipping_address->address }}</div>

                                    <div>{{ $invoice->order->shipping_address->postcode . ' ' . $invoice->order->shipping_address->city }}</div>

                                    <div>{{ $invoice->order->shipping_address->state . ', ' . core()->country_name($invoice->order->shipping_address->country) }}</div>

                                    <div>@lang('shop::app.customers.account.orders.invoice-pdf.contact'): {{ $invoice->order->shipping_address->phone }}</div>
                                </td>
                            @endif
                        </tr>
                    </tbody>
                </table>

                <!-- Payment & Shipping Methods -->
                <table class="{{ core()->getCurrentLocale()->direction }}">
                    <thead>
                        <tr>
                            <th style="width: 50%">
                                <b>
                                    @lang('shop::app.customers.account.orders.invoice-pdf.payment-method')
                                </b>
                            </th>

                            @if ($invoice->order->shipping_address)
                                <th style="width: 50%">
                                    <b>
                                        @lang('shop::app.customers.account.orders.invoice-pdf.shipping-method')
                                    </b>
                                </th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td style="width: 50%">
                                {{ core()->getConfigData('sales.payment_methods.' . $invoice->order->payment->method . '.title') }}

                                @php $additionalDetails = \Webkul\Payment\Payment::getAdditionalDetails($invoice->order->payment->method); @endphp

                                @if (! empty($additionalDetails))
                                    <div class="row small-text">
                                        <span>{{ $additionalDetails['title'] }}:</span>

                                        <span>{{ $additionalDetails['value'] }}</span>
                                    </div>
                                @endif
                            </td>

                            @if ($invoice->order->shipping_address)
                                <td style="width: 50%">
                                    {{ $invoice->order->shipping_title }}
                                </td>
                            @endif
                        </tr>
                    </tbody>
                </table>

                <!-- Items -->
                <div class="items">
                    <table class="{{ core()->getCurrentLocale()->direction }}">
                        <thead>
                            <tr>
                                <th>
                                    @lang('shop::app.customers.account.orders.invoice-pdf.sku')
                                </th>

                                <th>
                                    @lang('shop::app.customers.account.orders.invoice-pdf.product-name')
                                </th>

                                <th>
                                    @lang('shop::app.customers.account.orders.invoice-pdf.price')
                                </th>

                                <th>
                                    @lang('shop::app.customers.account.orders.invoice-pdf.qty')
                                </th>

                                <th>
                                    @lang('shop::app.customers.account.orders.invoice-pdf.subtotal')
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($invoice->items as $item)
                                <tr>
                                    <td>
                                        {{ $item->getTypeInstance()->getOrderedItem($item)->sku }}
                                    </td>

                                    <td>
                                        {{ $item->name }}

                                        @if (isset($item->additional['attributes']))
                                            <div>
                                                @foreach ($item->additional['attributes'] as $attribute)
                                                    @if (
                                                        ! isset($attribute['attribute_type'])
                                                        || $attribute['attribute_type'] !== 'file'
                                                    )
                                                        <b>{{ $attribute['attribute_name'] }} : </b>{{ $attribute['option_label'] }}<br>
                                                    @else
                                                        {{ $attribute['attribute_name'] }} :

                                                        <a
                                                            href="{{ Storage::url($attribute['option_label']) }}"
                                                            class="text-blue-600 hover:underline"
                                                            download="{{ File::basename($attribute['option_label']) }}"
                                                        >
                                                            {{ File::basename($attribute['option_label']) }}
                                                        </a>

                                                        <br>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        @if (core()->getConfigData('sales.taxes.sales.display_prices') == 'including_tax')
                                            {!! core()->formatPrice($item->price_incl_tax, $orderCurrencyCode) !!}
                                        @elseif (core()->getConfigData('sales.taxes.sales.display_prices') == 'both')
                                            {!! core()->formatPrice($item->price_incl_tax, $orderCurrencyCode) !!}

                                            <div class="small-text">
                                                @lang('shop::app.customers.account.orders.invoice-pdf.excl-tax')

                                                <span>
                                                    {{ core()->formatPrice($item->price, $orderCurrencyCode) }}
                                                </span>
                                            </div>
                                        @else
                                            {!! core()->formatPrice($item->price, $orderCurrencyCode) !!}
                                        @endif
                                    </td>

                                    <td>
                                        {{ $item->qty }}
                                    </td>

                                    <td>
                                        @if (core()->getConfigData('sales.taxes.sales.display_subtotal') == 'including_tax')
                                            {!! core()->formatPrice($item->total_incl_tax, $orderCurrencyCode) !!}
                                        @elseif (core()->getConfigData('sales.taxes.sales.display_subtotal') == 'both')
                                            {!! core()->formatPrice($item->total_incl_tax, $orderCurrencyCode) !!}

                                            <div class="small-text">
                                                @lang('shop::app.customers.account.orders.invoice-pdf.excl-tax')

                                                <span>
                                                    {{ core()->formatPrice($item->total, $orderCurrencyCode) }}
                                                </span>
                                            </div>
                                        @else
                                            {!! core()->formatPrice($item->total, $orderCurrencyCode) !!}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Summary Table -->
                <div class="summary">
                    <table class="{{ core()->getCurrentLocale()->direction }}">
                        <tbody>
                            @if (core()->getConfigData('sales.taxes.sales.display_subtotal') == 'including_tax')
                                <tr>
                                    <td>@lang('shop::app.customers.account.orders.invoice-pdf.subtotal')</td>
                                    <td>-</td>
                                    <td>{!! core()->formatPrice($invoice->sub_total_incl_tax, $orderCurrencyCode) !!}</td>
                                </tr>
                            @elseif (core()->getConfigData('sales.taxes.sales.display_subtotal') == 'both')
                                <tr>
                                    <td>@lang('shop::app.customers.account.orders.invoice-pdf.subtotal-incl-tax')</td>
                                    <td>-</td>
                                    <td>{!! core()->formatPrice($invoice->sub_total_incl_tax, $orderCurrencyCode) !!}</td>
                                </tr>

                                <tr>
                                    <td>@lang('shop::app.customers.account.orders.invoice-pdf.subtotal-excl-tax')</td>
                                    <td>-</td>
                                    <td>{!! core()->formatPrice($invoice->sub_total, $orderCurrencyCode) !!}</td>
                                </tr>
                            @else
                                <tr>
                                    <td>@lang('shop::app.customers.account.orders.invoice-pdf.subtotal')</td>
                                    <td>-</td>
                                    <td>{!! core()->formatPrice($invoice->sub_total, $orderCurrencyCode) !!}</td>
                                </tr>
                            @endif

                            @if (core()->getConfigData('sales.taxes.sales.display_shipping_amount') == 'including_tax')
                                <tr>
                                    <td>@lang('shop::app.customers.account.orders.invoice-pdf.shipping-handling')</td>
                                    <td>-</td>
                                    <td>{!! core()->formatPrice($invoice->shipping_amount_incl_tax, $orderCurrencyCode) !!}</td>
                                </tr>
                            @elseif (core()->getConfigData('sales.taxes.sales.display_shipping_amount') == 'both')
                                <tr>
                                    <td>@lang('shop::app.customers.account.orders.invoice-pdf.shipping-handling-incl-tax')</td>
                                    <td>-</td>
                                    <td>{!! core()->formatPrice($invoice->shipping_amount_incl_tax, $orderCurrencyCode) !!}</td>
                                </tr>

                                <tr>
                                    <td>@lang('shop::app.customers.account.orders.invoice-pdf.shipping-handling-excl-tax')</td>
                                    <td>-</td>
                                    <td>{!! core()->formatPrice($invoice->shipping_amount, $orderCurrencyCode) !!}</td>
                                </tr>
                            @else
                                <tr>
                                    <td>@lang('shop::app.customers.account.orders.invoice-pdf.shipping-handling')</td>
                                    <td>-</td>
                                    <td>{!! core()->formatPrice($invoice->shipping_amount, $orderCurrencyCode) !!}</td>
                                </tr>
                            @endif

                            <tr>
                                <td>@lang('shop::app.customers.account.orders.invoice-pdf.tax')</td>
                                <td>-</td>
                                <td>{!! core()->formatPrice($invoice->tax_amount, $orderCurrencyCode) !!}</td>
                            </tr>

                            <tr>
                                <td>@lang('shop::app.customers.account.orders.invoice-pdf.discount')</td>
                                <td>-</td>
                                <td>{!! core()->formatPrice($invoice->discount_amount, $orderCurrencyCode) !!}</td>
                            </tr>

                            <tr>
                                <td style="border-top: 1px solid #FFFFFF;">
                                    <b>@lang('shop::app.customers.account.orders.invoice-pdf.grand-total')</b>
                                </td>
                                <td style="border-top: 1px solid #FFFFFF;">-</td>
                                <td style="border-top: 1px solid #FFFFFF;">
                                    <b>{!! core()->formatPrice($invoice->grand_total, $orderCurrencyCode) !!}</b>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </body>
</html>
