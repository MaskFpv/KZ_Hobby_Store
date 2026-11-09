<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://fonts.googleapis.com/css2?family=M+PLUS+Rounded+1c:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link rel="icon" href="{{ asset('img/logo.svg') }}" type="image/svg+xml">
    <title>Nota {{ $data->id }} · {{ config('store.name') }}</title>
    <style>
        body {
            margin-top: 20px;
            background-color: #eee;
        }

        .card {
            box-shadow: 0 20px 27px 0 rgb(0 0 0 / 5%);
        }

        .card {
            position: relative;
            display: flex;
            flex-direction: column;
            min-width: 0;
            word-wrap: break-word;
            background-color: #fff;
            background-clip: border-box;
            border: 0 solid rgba(0, 0, 0, .125);
            border-radius: 1rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="card ">
                    <div class="card-body p-5">
                        <div class="invoice-title">
                            <h4 class="float-end font-size-15">Nota : {{ $data->id }} </h4>
                            <div class="mb-3 d-flex align-items-center gap-3">
                                <img src="{{ asset('img/logo.svg') }}" alt="" width="48" height="48">
                                <h2 class="mb-0">{{ config('store.name') }}</h2>
                            </div>
                            <div class="text-muted">
                                <p class="mb-1">{{ config('store.address') }}</p>
                                <p class="mb-1">Kasir: {{ $data->user->name ?? '-' }}</p>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            <div class="col-6 mt-2">
                                <div class="text-muted">
                                    @php $isMember = $data->customer && $data->customer->kode_member !== 'MBR00000'; @endphp
                                    <h5 class="font-size-15">
                                        Nama : {{ $data->customer->nama ?? 'Pelanggan Umum' }}
                                    </h5>
                                    @if ($isMember)
                                        <p class="mb-1">Member : {{ $data->customer->kode_member }}</p>
                                        @if ($data->customer->telepon)
                                            <p class="mb-1">Telepon : {{ $data->customer->telepon }}</p>
                                        @endif
                                        @if ($data->customer->email)
                                            <p class="mb-1">Email : {{ $data->customer->email }}</p>
                                        @endif
                                    @endif
                                </div>
                            </div>
                            <!-- end col -->
                            <div class="col-6 mt-2">
                                <div class="text-muted text-sm-end">
                                    <div class="font-size-14">
                                        <h5 class="font-size-15 mb-1">Tanggal Pembelian:</h5>
                                        <p class="mb-1">{{ \Carbon\Carbon::parse($data->date)->format('d M Y') }}</p>
                                        <p>Pembayaran: {{ $data->payment_method_label }}</p>
                                    </div>
                                </div>
                            </div>
                            <!-- end col -->
                        </div>
                        <!-- end row -->

                        @if ($data->preOrder)
                            <div class="alert alert-info mt-4 mb-0">
                                Pelunasan Pre-Order <b>{{ $data->preOrder->code }}</b>
                                (dipesan {{ $data->preOrder->order_date->format('d M Y') }}).
                                Pembayaran:
                                @foreach ($data->preOrder->payments as $pay)
                                    {{ $pay->type_label }} Rp {{ number_format($pay->amount, 0, ',', '.') }}{{ !$loop->last ? ',' : '.' }}
                                @endforeach
                            </div>
                        @endif

                        <div class="py-2 mt-5">
                            <h5 class="fs-4">Detail Pembelian :</h5>

                            <div class="table-responsive">
                                <table class="table align-middle table-nowrap table-centered mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 70px;">No.</th>
                                            <th>Produk</th>
                                            <th>Harga</th>
                                            <th>Jumlah</th>
                                            <th class="text-end" style="width: 160px;">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($data->transactionDetails as $item)
                                            <tr>
                                                <th scope="row">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</th>
                                                <td>{{ $item->product_name }}</td>
                                                <td>Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                                <td>{{ $item->qty }}</td>
                                                <td class="text-end">Rp {{ number_format($item->sub_total, 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <table class="table table-borderless w-auto ms-auto mt-3 mb-0">
                                <tr>
                                    <td class="text-end">Subtotal</td>
                                    <td class="text-end">Rp {{ number_format($data->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @if ($data->discount > 0)
                                    <tr>
                                        <td class="text-end">Diskon</td>
                                        <td class="text-end text-danger">- Rp {{ number_format($data->discount, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                                <tr class="fs-5 fw-bold">
                                    <td class="text-end">Total</td>
                                    <td class="text-end">Rp {{ number_format($data->total_price, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-end">Dibayar</td>
                                    <td class="text-end">Rp {{ number_format($data->paid_amount, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-end">Kembalian</td>
                                    <td class="text-end">Rp {{ number_format($data->change_amount, 0, ',', '.') }}</td>
                                </tr>
                            </table>
                            <div class="d-print-none mt-4">
                                <div class="float-end">
                                    <button type="button" onclick="window.print()" class="btn btn-success me-1">
                                        <i class="bi bi-printer"></i> Print
                                    </button>
                                    @php
                                        $back = url()->previous() !== url()->current() ? url()->previous() : route('transaction.data');
                                    @endphp
                                    <a href="{{ $back }}" class="btn btn-light">Kembali</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
