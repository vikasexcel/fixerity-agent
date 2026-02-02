let map, marker, geocoder, infowindow;

// Initialize Google Map and autocomplete logic
async function initialize() {
    console.log("initialize");
    console.log(lati);
    console.log(longi);
    // Load required Google Maps libraries
    const {Map} = await google.maps.importLibrary("maps");
    const {Marker} = await google.maps.importLibrary("marker");

    // Set initial position using predefined latitude and longitude
    var latlng = new google.maps.LatLng(lati, longi);

    console.log('latlng');
    console.log(latlng);
    // Create the map instance centered on initial position
    map = new Map(document.getElementById("map"), {
        center: latlng,
        zoom: 10, // Initial zoom level (can be adjusted)
    });

    // Create a draggable marker placed at the initial position
    marker = new Marker({
        map,
        position: latlng,
        draggable: true, // Enables manual repositioning
    });

    // Initialize the geocoder for reverse geocoding coordinates to address
    geocoder = new google.maps.Geocoder();

    // InfoWindow is a popup bubble on the map showing address info
    infowindow = new google.maps.InfoWindow();

    // If we already have an address, show it in the search input field
    if (address) {
        document.getElementById("searchInput").value = address;
    }

    // When user drags the marker, update the address fields
    marker.addListener("dragend", () => {
        const pos = marker.getPosition();

        // Convert marker's new position to human-readable address
        geocoder.geocode({location: pos}, (results, status) => {
            if (status === "OK" && results[0]) {
                // Update the form fields and show address in the InfoWindow
                bindDataToForm(results[0].formatted_address, pos.lat(), pos.lng());
                // infowindow.setContent(results[0].formatted_address);
                // infowindow.open(map, marker);
            }
        });
    });

    // Use a debounced function to limit how often suggestions are fetched
    const debouncedSuggestions = debounce(fetchSuggestions, 1000);
    // Add event listener to search field for live suggestions
    document.getElementById("searchInput").addEventListener("input", debouncedSuggestions);
    // attach keyup event to search address
    $("#searchInput").on('keyup', function() {
        // set address lat and long to empty because user have to select it from search suggestion
        $(".address-error").text('');
        $("#lat").val('');
        $("#lng").val('');
        $("#getaddress").val('');
    });
    // Disable Enter key in inputs (except submit) to avoid accidental form submits
    $("#adduser input").not("#submit").keydown(function (event) {
        if (event.keyCode === 13) {
            event.preventDefault(); // Stop default form submission
            return false;
        }
    });
}

// Generates a UUIDv4 string to uniquely identify session (required by Places API)
function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        const r = Math.random() * 16 | 0,
            v = c === 'x' ? r : (r & 0x3 | 0x8); // Follow UUIDv4 bit structure
        return v.toString(16);
    });
}

let sessionToken = null; // Token reused between suggestion & details requests

// Fetches place predictions from Google Places API based on user input
async function fetchSuggestions(e) {
    const input = e.target.value.trim();
    const suggestionsDiv = document.getElementById("suggestions");

    // Generate a session token if not already created
    if (!sessionToken) {
        sessionToken = generateUUID();
    }

    // Avoid making request for very short inputs
    if (input.length < 2) {
        suggestionsDiv.innerHTML = "";
        return;
    }

    // Prepare request body for Places Autocomplete API v1
    const payload = {
        input: input,
        languageCode: "en",
        sessionToken: sessionToken,
        locationBias: {
            circle: {
                center: {
                    latitude: lati,
                    longitude: longi
                },
                radius: 50000 // Suggest locations within 50km
            }
        }
    };

    try {
        const res = await fetch("https://places.googleapis.com/v1/places:autocomplete", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Goog-Api-Key": apiKey,           // Your API Key
                "X-Goog-FieldMask": "suggestions.placePrediction.placeId,suggestions.placePrediction.structuredFormat,suggestions.placePrediction.text"             // Get all fields in response
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        suggestionsDiv.innerHTML = ""; // Clear previous suggestions

        // Handle server-side API errors
        if (!res.ok) {
            console.error("Suggestions error:", data);
            return;
        }

        // Display fetched suggestions as a clickable dropdown list
        if (data.suggestions) {
            data.suggestions.forEach(placePrediction => {
                const div = document.createElement("div");
                div.style.padding = "8px";
                div.style.cursor = "pointer";
                div.style.borderBottom = "1px solid #eee";
                div.style.display = "flex"; // Use flexbox for layout
                div.style.alignItems = "center"; // Vertically align items (icon and text)

                // Marker icon (Google-style)
                const icon = document.createElement("span");
                icon.innerHTML = '<i class="fas fa-map-marker-alt"></i>';
                icon.style.marginRight = "8px"; // Space between icon and text
                icon.style.fontSize = "16px";

                // Add icon first
                div.appendChild(icon);

                // Add text after icon
                const text = document.createElement("span");
                text.innerText = placePrediction.placePrediction.text.text;
                div.appendChild(text);

                // Highlight suggestion on hover
                div.onmouseover = () => div.style.backgroundColor = "#f0f0f0";
                div.onmouseout = () => div.style.backgroundColor = "#fff";

                // When suggestion is clicked, fetch full place details
                div.onclick = () => {
                    fetchPlaceDetails(placePrediction.placePrediction.placeId, apiKey);
                    sessionToken = generateUUID(); // Reset token for next interaction
                    suggestionsDiv.innerHTML = ""; // Clear dropdown
                };

                suggestionsDiv.appendChild(div);
            });
        } else {
            // Handle case with no results
            const noRes = document.createElement("div");
            noRes.innerText = "No suggestions found";
            noRes.style.padding = "8px";
            noRes.style.backgroundColor = "#ffeaea";
            noRes.style.color = "#a94442";
            noRes.style.borderBottom = "1px solid #f5c2c2";
            noRes.style.fontSize = "14px";
            noRes.style.textAlign = "left";
            noRes.style.textIndent = "20px";
            noRes.style.cursor = "default";
            noRes.style.borderRadius = "6px";
            noRes.style.marginBottom = "6px";
            document.getElementById("suggestions").style.padding = '0';
            suggestionsDiv.appendChild(noRes);
        }

    } catch (err) {
        console.error("Autocomplete error:", err);
        suggestionsDiv.innerHTML = "";
    }
}

// Fetch full details about a selected place (after clicking a suggestion)
async function fetchPlaceDetails(placeId, apiKey) {
    const res = await fetch(`https://places.googleapis.com/v1/places/${placeId}?fields=location,displayName,formattedAddress&key=${apiKey}`, {
        method: "GET",
        headers: {
            "X-Goog-Api-Key": apiKey, // Auth with API key
        },
    });

    const data = await res.json();

    // Extract useful info from the response
    const lat = data.location.latitude;
    const lng = data.location.longitude;
    const address = data.formattedAddress || data.displayName.text;

    // Move map and marker to selected location
    const latlng = new google.maps.LatLng(lat, lng);
    map.setCenter(latlng);
    // map.setZoom(17); // Zoom in close to selected place
    marker.setPosition(latlng);
    marker.setVisible(true);

    // Update form fields and show InfoWindow
    bindDataToForm(address, lat, lng);
    // infowindow.setContent(address);
    // infowindow.open(map, marker);
}

// Fills in form fields with selected or dragged location
function bindDataToForm(address, lat, lng) {
    $(".address-error").text('');
    document.getElementById("getaddress").value = address;
    document.getElementById("searchInput").value = address;
    document.getElementById("lat").value = lat;
    document.getElementById("lng").value = lng;
}

// Delay a function execution until after `delay` ms of inactivity
function debounce(func, delay) {
    let timer;
    return function (...args) {
        clearTimeout(timer); // Cancel any scheduled execution
        timer = setTimeout(() => func.apply(this, args), delay); // Schedule new one
    };
}
// Clear suggestions when clicking outside the input field and suggestion dropdown
const searchInput = document.getElementById("searchInput");
const suggestionsDiv = document.getElementById("suggestions");
document.addEventListener('click', function (event) {
    const clickedOutsideInput = !searchInput.contains(event.target);
    const clickedOutsideSuggestions = !suggestionsDiv.contains(event.target);

    if (clickedOutsideInput && clickedOutsideSuggestions) {
        suggestionsDiv.innerHTML = '';
    }
});
