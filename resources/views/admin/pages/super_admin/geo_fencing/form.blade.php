@extends('admin.layout.super_admin')
@section('title')
    @if(isset($area_details)) Edit @else Add @endif Restricted Area
@endsection
@section('page-css')
    <style>
        input[type="radio"] {
            display: none;
        }

        input[type="radio"] + .label {
            position: relative;
            /*margin-left: 43%;*/
            /*display: block;*/
            padding-left: 25px;
            margin-right: 10px;
            cursor: pointer;
            /*line-height: 16px;*/
            color: black;
            font-size: 14px;
            transition: all .2s ease-in-out;
            margin-bottom: 10px;
        }

        input[type="radio"] + .label:before, input[type="radio"] > .label:after {
            content: '';
            position: absolute;
            top: -1px;
            left: 0;
            width: 20px;
            height: 20px;
            text-align: center;
            color: black;
            cursor: pointer;
            border-radius: 50%;
            transition: all .3s ease;
        }

        input[type="radio"] + .label:before {
            /*box-shadow: inset 0 0 0 1px #666565, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #44BB6E;*/
            box-shadow: 0 0 0 0 #91DEAC, inset 0 0 0 2px #FFFFFF, inset 0 0 0 3px #44BB6E, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #44BB6E;
        }

        input[type="radio"] + .label:hover {
            color: #44BB6E;
        }

        input[type="radio"] + .label:hover:before {
            animation-duration: .5s;
            animation-name: change-size;
            animation-iteration-count: infinite;
            animation-direction: alternate;
            box-shadow: inset 0 0 0 1px #44BB6E, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #44BB6E;
        }

        input[type="radio"]:checked + .label:hover {
            color: #333333;
            cursor: default;
        }

        input[type="radio"]:checked + .label:before {
            animation-duration: .2s;
            animation-name: select-radio;
            animation-iteration-count: 1;
            animation-direction: Normal;
            box-shadow: inset 0 0 0 1px #44BB6E, inset 0 0 0 3px #FFFFFF, inset 0 0 0 16px #44BB6E;

        }

        @keyframes change-size {
            from {
                box-shadow: 0 0 0 0 #44BB6E, inset 0 0 0 1px #44BB6E, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #44BB6E;
            }
            to {
                box-shadow: 0 0 0 1px #44BB6E, inset 0 0 0 1px #44BB6E, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #44BB6E;
            }
        }

        @keyframes select-radio {
            0% {
                box-shadow: 0 0 0 0 #91DEAC, inset 0 0 0 2px #FFFFFF, inset 0 0 0 3px #44BB6E, inset 0 0 0 16px #FFFFFF, inset 0 0 0 16px #44BB6E;
            }
            90% {
                box-shadow: 0 0 0 10px #E8FFF0, inset 0 0 0 0 #FFFFFF, inset 0 0 0 1px #44BB6E, inset 0 0 0 2px #FFFFFF, inset 0 0 0 16px #44BB6E;
            }
            100% {
                box-shadow: 0 0 0 12px #E8FFF0, inset 0 0 0 0 #FFFFFF, inset 0 0 0 1px #44BB6E, inset 0 0 0 3px #FFFFFF, inset 0 0 0 16px #44BB6E;
            }
        }

        @media screen and (max-width: 576px) {
            input[type="radio"] + .label {
                margin-left: 48%;
                display: block;
            }
        }
        #searchInput,#suggestions {
            background-color: #fff;
            font-family: Roboto;
            font-size: 16px;
            font-weight: 300;
            /*margin-left: 12px;*/
            /*padding: 0 11px 0 13px;*/
            text-overflow: ellipsis;
            width: 100%;
        }
    </style>
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
                            <h5>Restricted Area</h5>
                            <span>@if(isset($area_details)) Edit @else Add @endif Restricted Area</span>
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
                        <div class="card">
                            <div class="card-header">
                                <h5>@if(isset($area_details)) Edit @else Add @endif Restricted Area</h5>
                                <a href="{{ route('get:admin:restricted_area_list') }}"
                                   class="btn btn-primary m-b-0 btn-right render_link"> Back</a>
                            </div>
                            <div class="card-block">
                                <form id="main" method="post" action="{{ route('post:admin:update_restricted_area') }}"
                                      enctype="multipart/form-data">
                                    {{csrf_field() }}

                                    @if(isset($area_details))
                                        <input type="hidden" name="id" value="{{$area_details->id}}">
                                    @endif
                                    <div class="row">
                                        <div class="form-group col-sm-12">
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Restricted Area Name:<sup
                                                        class="error">*</sup></label>
                                                <div class="col-sm-9">
                                                    <input type="text" class="form-control" name="area_name" required
                                                           id="area_name" placeholder="Restricted Area Name"
                                                           value="{{ (isset($area_details)) ? $area_details->name : old('name') }}">
                                                    <span class="error">{{ $errors->first('name') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="latitude" id="lat" value="{{ isset($area_details) ? $area_details->latitude : '' }}">
                                        <input type="hidden" name="longitude" id="lang" value="{{ isset($area_details) ? $area_details->longitude : '' }}">
                                        <div class="form-group col-sm-12">
                                            <div class="form-group row">
                                                <div class="col-sm-12">
                                                    <div class="form-group">
                                                        <input id="searchInput" name="store_address"
                                                               class="input-controls form-control my-2"
                                                               value="{{ (isset($area_details)) ? $area_details->name : old('store_address')}}"
                                                               type="text" placeholder="Enter a location">
                                                        <!-- Suggestions dropdown -->
                                                        <div id="suggestions"></div>
                                                        <div class="map" id="map"
                                                             style="width: 100%; height: 500px;"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <label class="col-sm-12"></label>
                                        <div class="col-sm-10">
                                            <button type="submit" class="btn btn-primary m-b-0 button_loader">Save</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Page body end -->
            </div>
        </div>
    </div>

@endsection
@section('page-js')
    @php $general_settings = \App\Models\GeneralSettings::first() @endphp
{{--    <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>--}}
    <script src="https://maps.googleapis.com/maps/api/js?key={{ isset($general_settings)? ($general_settings->map_key != Null)? $general_settings->map_key : 0 : 0 }}&callback=initMap&v=weekly" defer ></script>
    <script>
        let map, marker, infowindow, geocoder, drawingManager, polygon;
        let sessionToken = null;

        const apiKey = '{{ $general_settings->map_key }}';
        const lati = parseFloat('{{ $general_settings->map_lat ?? "0" }}');
        const longi = parseFloat('{{ $general_settings->map_long ?? "0" }}');

        $(document).ready(function () {
            $(window).keydown(function (event) {
                if (event.keyCode == 13) {
                    event.preventDefault();
                    return false;
                }
            });
        });

        async function initMap() {
            const { Map } = await google.maps.importLibrary("maps");
            const { Marker } = await google.maps.importLibrary("marker");
            const { DrawingManager } = await google.maps.importLibrary("drawing");

            const center = new google.maps.LatLng(lati, longi);

            map = new Map(document.getElementById("map"), {
                center: center,
                zoom: 10,
            });

            geocoder = new google.maps.Geocoder();
            infowindow = new google.maps.InfoWindow();

            marker = new Marker({
                map,
                // position: center,
                // draggable: true,
                // visible: true
            });

            marker.addListener("dragend", () => {
                const pos = marker.getPosition();
                geocoder.geocode({ location: pos }, (results, status) => {
                    if (status === "OK" && results[0]) {
                        bindDataToForm(results[0].formatted_address, pos.lat(), pos.lng());
                        // infowindow.setContent(results[0].formatted_address);
                        // infowindow.open(map, marker);
                    }
                });
            });

            drawingManager = new DrawingManager({
                drawingMode: google.maps.drawing.OverlayType.POLYGON,
                drawingControl: true,
                drawingControlOptions: {
                    position: google.maps.ControlPosition.TOP_CENTER,
                    drawingModes: [google.maps.drawing.OverlayType.POLYGON],
                },
                polygonOptions: {
                    strokeColor: "#000",
                    strokeOpacity: 0.8,
                    strokeWeight: 5,
                    fillColor: "#8e8585",
                    fillOpacity: 0.35,
                    editable: true,
                    draggable: true,
                },
            });

            drawingManager.setMap(map);

            google.maps.event.addListener(drawingManager, 'overlaycomplete', function (event) {
                if (polygon) polygon.setMap(null);
                polygon = event.overlay;
                updatePolygonCoordinates(polygon);
                attachPolygonListeners(polygon);
                attachPolygonClickHandler(polygon);
            });

            // Restore existing polygon if editing
            const latVals = document.getElementById("lat").value;
            const lngVals = document.getElementById("lang").value;

            if (latVals && lngVals) {
                const latArr = latVals.split(",").map(Number);
                const lngArr = lngVals.split(",").map(Number);

                if (latArr.length === lngArr.length && latArr.length > 2) {
                    const polygonCoords = latArr.map((lat, i) => ({ lat, lng: lngArr[i] }));

                    polygon = new google.maps.Polygon({
                        paths: polygonCoords,
                        strokeColor: "#000",
                        strokeOpacity: 0.8,
                        strokeWeight: 5,
                        fillColor: "#8e8585",
                        fillOpacity: 0.35,
                        editable: true,
                        draggable: true,
                    });

                    polygon.setMap(map);
                    attachPolygonListeners(polygon);
                    attachPolygonClickHandler(polygon);
                    updatePolygonCoordinates(polygon);

                    const bounds = new google.maps.LatLngBounds();
                    polygon.getPath().forEach(latlng => bounds.extend(latlng));
                    map.fitBounds(bounds);

                    // Center marker at polygon center
                    const polyCenter = bounds.getCenter();
                    marker.setPosition(polyCenter);
                    marker.setVisible(false);

                    // Optional: reverse geocode center
                    geocoder.geocode({ location: polyCenter }, (results, status) => {
                        if (status === "OK" && results[0]) {
                            bindDataToForm(results[0].formatted_address, polyCenter.lat(), polyCenter.lng());
                            // infowindow.setContent(results[0].formatted_address);
                            // infowindow.open(map, marker);
                        }
                    });
                }
            }

            const debouncedSuggestions = debounce(fetchSuggestions, 1000);
            document.getElementById("searchInput").addEventListener("input", debouncedSuggestions);
        }

        function attachPolygonClickHandler(polygonInstance) {
            google.maps.event.addListener(polygonInstance, 'click', function (event) {
                const latlng = event.latLng;
                marker.setPosition(latlng);
                marker.setVisible(false);

                geocoder.geocode({ location: latlng }, (results, status) => {
                    if (status === "OK" && results[0]) {
                        bindDataToForm(results[0].formatted_address, latlng.lat(), latlng.lng());
                        // infowindow.setContent(results[0].formatted_address);
                        // infowindow.open(map, marker);
                    }
                });
            });
        }

        function generateUUID() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                const r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }

        async function fetchSuggestions(e) {
            const input = e.target.value.trim();
            const suggestionsDiv = document.getElementById("suggestions");

            if (!sessionToken) sessionToken = generateUUID();

            if (input.length < 2) {
                suggestionsDiv.innerHTML = "";
                return;
            }

            const payload = {
                input,
                languageCode: "en",
                sessionToken,
                locationBias: {
                    circle: {
                        center: {
                            latitude: lati,
                            longitude: longi,
                        },
                        radius: 50000,
                    },
                },
            };

            try {
                const res = await fetch("https://places.googleapis.com/v1/places:autocomplete", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Goog-Api-Key": apiKey,
                        "X-Goog-FieldMask": "*",
                    },
                    body: JSON.stringify(payload),
                });

                const data = await res.json();
                suggestionsDiv.innerHTML = "";

                if (res.ok && data.suggestions) {
                    data.suggestions.forEach(suggestion => {
                        const div = document.createElement("div");
                        div.innerText = suggestion.placePrediction.text.text;
                        div.className = "suggestion-item";
                        Object.assign(div.style, {
                            padding: "8px",
                            cursor: "pointer",
                            borderBottom: "1px solid #eee"
                        });
                        div.onclick = () => {
                            fetchPlaceDetails(suggestion.placePrediction.placeId);
                            sessionToken = generateUUID();
                            suggestionsDiv.innerHTML = "";
                        };
                        suggestionsDiv.appendChild(div);
                    });
                } else {
                    const noRes = document.createElement("div");
                    noRes.innerText = "No suggestions found";
                    Object.assign(noRes.style, {
                        padding: "8px",
                        backgroundColor: "#ffeaea",
                        color: "#a94442",
                        borderBottom: "1px solid #f5c2c2",
                        fontSize: "14px",
                        textAlign: "left",
                        textIndent: "20px",
                        cursor: "default",
                        borderRadius: "6px",
                        marginBottom: "6px"
                    });
                    suggestionsDiv.appendChild(noRes);
                }
            } catch (err) {
                console.error("Autocomplete error:", err);
                suggestionsDiv.innerHTML = "";
            }
        }

        async function fetchPlaceDetails(placeId) {
            try {
                const res = await fetch(`https://places.googleapis.com/v1/places/${placeId}?fields=location,displayName,formattedAddress&key=${apiKey}`, {
                    headers: {
                        "X-Goog-Api-Key": apiKey,
                    }
                });
                const data = await res.json();
                if (data.location) {
                    const lat = data.location.latitude;
                    const lng = data.location.longitude;
                    const address = data.formattedAddress || data.displayName.text;
                    const latlng = new google.maps.LatLng(lat, lng);

                    map.setCenter(latlng);
                    // map.setZoom(17);
                    marker.setPosition(latlng);
                    marker.setVisible(false);
                    bindDataToForm(address, null, null);
                    // infowindow.setContent(address);
                    // infowindow.open(map, marker);
                }
            } catch (err) {
                console.error("Place details fetch error:", err);
            }
        }

        function updatePolygonCoordinates(polygonInstance) {
            const path = polygonInstance.getPath().getArray();
            const latList = [], lngList = [];
            path.forEach(latlng => {
                latList.push(latlng.lat());
                lngList.push(latlng.lng());
            });
            $("#lat").val(latList.join(","));
            $("#lang").val(lngList.join(","));
        }

        function attachPolygonListeners(polygonInstance) {
            const path = polygonInstance.getPath();
            google.maps.event.clearListeners(path, 'insert_at');
            google.maps.event.clearListeners(path, 'remove_at');
            google.maps.event.clearListeners(path, 'set_at');

            ['insert_at', 'remove_at', 'set_at'].forEach(eventName => {
                google.maps.event.addListener(path, eventName, () => {
                    updatePolygonCoordinates(polygonInstance);
                });
            });
        }

        function bindDataToForm(address, lat, lng) {
            $("#getaddress").val(address);
            $("#searchInput").val(address);
            // $("#lat").val(lat);
            // $("#lang").val(lng);
        }

        function debounce(func, delay) {
            let timer;
            return function (...args) {
                clearTimeout(timer);
                timer = setTimeout(() => func.apply(this, args), delay);
            };
        }

        document.addEventListener('click', function (event) {
            const suggestionsDiv = document.getElementById("suggestions");
            const clickedOutsideInput = !searchInput.contains(event.target);
            const clickedOutsideSuggestions = !suggestionsDiv.contains(event.target);

            if (clickedOutsideInput && clickedOutsideSuggestions) {
                suggestionsDiv.innerHTML = '';
            }
        });

        // Disable button after form submission
        $('#main').on('submit', function() {
            $('.button_loader').attr('disabled', true);  // Disable the button
        });
    </script>
@endsection
