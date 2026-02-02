@extends('admin.layout.other_service')
@section('title')
    Wallet Transaction
@endsection
@section('page-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive/responsive.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" media="screen"
          href="{{ asset('assets/css/bootstrap-datetimepicker.min.css') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css" rel="stylesheet"/>
@endsection
@section('page-content')

    <div class="pcoded-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header card">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="feather icon-edit-1 bg-c-blue"></i>
                        <div class="d-inline">
                            <h5>Wallet Transaction</h5>
                            <span>Wallet Transaction</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <div class="pcoded-inner-content">
            <div class="main-body">
                <div class="page-wrapper">
                    <!-- Page body start -->
                    <div class="page-body">
                        <div class="row">
                            <div class="form-group col-sm-12">
                                <form action="{{ route('post:provider-admin:wallet') }}" id="add_wallet_frm" name="add_wallet_frm" method="post" enctype="multipart/form-data">
                                    {{csrf_field() }}
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>Wallet Transaction</h5>
                                            {{--<a href="{{ route('get:admin:user_list') }}"--}}
                                            {{--class="btn btn-primary m-b-0 btn-right render_link"> Back</a>--}}
                                        </div>
                                        <div class="form-row fieldRow mb-2 text-center" >
                                            <div class="form-group col-12 pt-4 mb-0 ">
                                                <label class="card-label currBalLbl">Current Balance</label>
                                            </div>
                                            <div class="form-group col-12">
                                                <label class="card-label currBalAmt font-weight-bold"><span class="currency">
                            @if(isset($total_avail_bal))
                                                            {{ ($total_avail_bal->remaining_balance > 0)?number_format($total_avail_bal->remaining_balance,2):0.00 }}
                                                        @else
                                                            0.00
                                                        @endif
                        </span></label>
                                            </div>
                                        </div>
                                        <div class="card-block">
                                            <div class="row">
                                                <div class="form-group col-sm-12">
                                                    <div class="form-group row">
                                                        <label class="col-sm-4 col-form-label">Recharge Amount:<sup
                                                                class="error">*</sup></label>
                                                        <div class="col-sm-8">
                                                            <input type="text" class="form-control"
                                                                   name="amount" id="amount"
                                                                   placeholder="Enter Amount"
                                                                   required autocomplete="off"
                                                                   value="{{ old('amount') }}">
                                                            <input type="hidden" class="form-control textBox" autocomplete="off" id="card_id" name="card_id" value="0" >
                                                            <span
                                                                class="error">{{ $errors->first('amount') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <center>
                                                <button type="submit" class="btn btn-primary m-b-0">Add Amount</button>
                                            </center>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="form-group col-sm-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Wallet Transaction</h5>
                                    </div>
                                    <div class="card-block">

                                        <div class="dt-responsive table-responsive">
                                            <table id="new-cons" class="table table-striped table-bordered nowrap"
                                                   style="width:100%">
                                                <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Subject</th>
                                                    <th>Date Time</th>
                                                    <th>Amount</th>
                                                </tr>
                                                </thead>
                                                <tbody>

                                                @if(isset($get_wallet_history) && count($get_wallet_history) > 0)
                                                    @foreach($get_wallet_history as $key => $singel_history)
                                                        @php
                                                            if($singel_history->transaction_type == 1){
                                                                $img_path = asset('/assets/front/img/png/transaction_plus.png');
                                                                $currency_color ="creditColor";
                                                                $currency_sign ="";

                                                            }else{
                                                                $img_path = asset('/assets/front/img/png/transection_up_arrow.png');
                                                                $currency_color ="debitColor";
                                                                $currency_sign ="-";
                                                            }
                                                        @endphp
                                                        <tr id="remove_card_{{ $singel_history->id }}">
                                                            <td>{{ $key+1 }}</td>
                                                            <td><img src="{{$img_path}}" height="25px" width="25px">{{ ($singel_history->subject!="")?$singel_history->subject:"--" }}</td>
{{--                                                            <td>{{ ($singel_history->created_at!="")?date("D d, M, Y, H:s a",strtotime($singel_history->created_at)):"" }}</td>--}}
                                                            <td data-utc="{{ $singel_history->created_at->toISOString() }}">
                                                                <span class="local-datetime"></span>
                                                            </td>
                                                            <td>{{($singel_history->amount>0)? $currency_sign . $singel_history->amount : 0}}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                                </tbody>
                                            </table>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
                <!-- Page body end -->
            </div>
        </div>
    </div>
    <div class="modal fade" id="paymentModalPopup" tabindex="-1" role="dialog" aria-labelledby="paymentModalPopup" aria-hidden="true">
        <div class="modal-dialog <!--modal-dialog-centered-->" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Money to Wallet</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <div class="cc-selector-2 pt-3 " id="card_lists">
                        @if(isset($card_list) && count($card_list) > 0)
                            <label class="font-weight-bold cardtextBorderBottom">Select Card</label><br/>
                            <div class="col-md-12">
                                <div class="row">
                                    @foreach($card_list as $key=>$single_card_list)
                                        <div class="col-md-6">
                                            <input id="card{{$single_card_list->id}}" {{ ($key == 0)?'checked="checked"':"" }} class="selectedCardCls"  type="radio" name="card_id" value="{{$single_card_list->id}}" />
                                            <label class="drinkcard-cc " for="card{{$single_card_list->id}}"><i class="fas fa-credit-card"></i> xx-{{ substr($single_card_list->card_number,-4) }}</label>
                                        </div>
                                    @endforeach
                                </div>

                            </div>
                        @else
                            <label class="font-weight-bold cardtextBorderBottom">Add New Card</label>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <div class=" col-6">
                        <a type="button" class="float-left btn btnColor btncouponcart" href="{{ route('get:provider-admin:manage_card') }}">Add New Card</a>
                    </div>
                    <div class=" col-6">
                        <button type="button" class="float-right btn btnColor btncouponcart addWalletBtn" @if(isset($card_list) && count($card_list) == 0) disabled @endif>Pay Now</button>
                        <button type="button" class="float-right btn btn-secondary mr-2" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('page-js')
    <script src="{{ asset('assets/js/responsive/jquery.dataTables.min.js')}}"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.bootstrap4.min.js')}}"></script>
    <script src="{{ asset('assets/js/responsive/dataTables.responsive.min.js')}}"></script>
    <script src="{{ asset('assets/js/responsive/responsive-custom.js')}}"></script>
    <script src="{{ asset('assets/js/validation/jquery.validate.js')}}"></script>
    <script>
        $(document).ready(function () {
            $("#add_wallet_frm").validate({
                rules: {
                    amount: {
                        required: true,
                        number: true,
                        min: 1
                    }
                },
                messages: {
                    amount: {
                        required: "Please enter amount",
                        number: "Please enter numeric value.",
                        min: "Amount must be greater than 0.",
                    }

                },
                errorPlacement: function (error, element) {
                    if (element.attr("name") == "expire_date") {
                        error.insertAfter(element.parent("div"));
                    } else {
                        error.insertAfter(element);
                    }
                },
                submitHandler: function (form) {
                    $('#paymentModalPopup').modal('show');
                }
            });

            $(document).on("click", ".addWalletBtn", function () {
                $('#paymentModalPopup').modal('hide');
                var card_id = $(".selectedCardCls").val();
                $("#card_id").val(card_id);
                // $("#add_wallet_frm").submit();
                $('#add_wallet_frm')[0].submit();
            });

        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Select all table cells with the data-utc attribute
            document.querySelectorAll('td[data-utc]').forEach(function(td) {
                // Get the UTC date from the data-utc attribute
                let utcDateStr = td.getAttribute('data-utc');

                // Parse the UTC date string into a Date object
                let utcDate = new Date(utcDateStr);

                // Check if the parsed date is valid
                if (!isNaN(utcDate)) {
                    // Convert the UTC date to the user's local time
                    let options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true };

                    // Format the date in the local timezone
                    let localDateStr = utcDate.toLocaleString(undefined, options);

                    // Convert AM/PM to uppercase
                    localDateStr = localDateStr.replace(/\s?(am|pm)$/i, function(match) {
                        return match.toUpperCase();
                    });

                    // Update the DOM with the formatted local time
                    td.querySelector('.local-datetime').textContent = localDateStr;
                } else {
                    td.querySelector('.local-datetime').textContent = "Invalid Date";
                }
            });
        });
    </script>
@endsection
