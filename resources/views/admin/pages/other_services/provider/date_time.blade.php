<div id="day_wise">
    <div class="form-group row" id="every_d">
        <div class="col-sm-12">
            <div class="border-checkbox-section row">
                <div class="border-checkbox-group border-checkbox-group-success">
                    <input name="all_day" value="1" class="border-checkbox" @if(!isset($all_day) || isset($all_day) && $all_day == 1) checked @endif type="checkbox" id="checkbox_every">
                    <label class="border-checkbox-label" for="checkbox_every"></label>
                    <span>EveryDay</span>
                </div>
            </div>
        </div>
    </div>
    <nav class="single_day"  id="single_day" >
        <div class="nav nav-pills nav-fill" id="nav-tab" role="tablist">
            @if(isset($days_arr))
                @foreach($days_arr as $key=>$single_day)
                    <a class="nav-link {{ ($key == 0  )?"active":"" }}" id="step{{$key}}-tab" data-id="{{ $single_day }}" data-bs-toggle="tab" href="#step{{$key}}">{{ $single_day }}</a>
                @endforeach
                <input type="hidden" name="activeday" id="activeday" value="SUN">
            @endif
        </div>
    </nav>
    <div class="tab-content py-4">
        @if(isset($time_slot_list))
            @php
                $i=0;
            @endphp
            @foreach($time_slot_list as $key_dayname=>$single_day_Arr)
                <div class="tab-pane fade  {{ ($i == 0  )?"show active":"" }}" id="step{{$i}}">
                    <div style="margin-top: 20px;" id="{{strtolower($key_dayname)}}_day_open_time" class="row">
                        @if(isset($single_day_Arr))
                            @foreach($single_day_Arr as $daykey=>$singel_slot_time)
                                <div class="col-sm-3">
                                    <input type="checkbox" class="btn-check open_time" id="{{$key_dayname}}_day_btn_check_{{$daykey}}" name="day_open_time[{{$key_dayname}}][{{$daykey}}]" value="{{$singel_slot_time['start_time']."-".$singel_slot_time['end_time']}}" {{ $singel_slot_time['selected'] ? "checked" : "" }}>
                                    <label class="btn btn-success font_clr {{$key_dayname}}_day_btn_check_{{$daykey}}" for="{{$key_dayname}}_day_btn_check_{{$daykey}}">{{$singel_slot_time['display_start_time']."-".$singel_slot_time['display_end_time']}}</label>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
                @php
                    $i++;
                @endphp
            @endforeach
        @endif
    </div>
</div>