function initAutocomplete() {
    const originInput = document.getElementById('origin');
    const destinationInput = document.getElementById('destination');

    if (originInput instanceof HTMLInputElement) {
        console.log('Initializing autocomplete for origin input');
        const originAutocomplete = new google.maps.places.Autocomplete(originInput);

        // Restrict the search to a specific country, e.g., the United States
        originAutocomplete.setComponentRestrictions({ 'country': ['us'] });

        // Add event listener to handle place changes and calculate distance
        originAutocomplete.addListener('place_changed', calculateDistance);
    } else {
        console.error('Element with ID "origin" must be an input element.');
    }

    if (destinationInput instanceof HTMLInputElement) {
        console.log('Initializing autocomplete for destination input');
        const destinationAutocomplete = new google.maps.places.Autocomplete(destinationInput);

        // Restrict the search to a specific country, e.g., the United States
        destinationAutocomplete.setComponentRestrictions({ 'country': ['us'] });

        // Add event listener to handle place changes and calculate distance
        destinationAutocomplete.addListener('place_changed', calculateDistance);
    } else if (destinationInput instanceof HTMLSelectElement) {
        console.log('Destination is a select element, skipping autocomplete initialization');
    } else {
        console.error('Element with ID "destination" must be an input or select element.');
    }
}

function calculateDistance() {
    var origin = document.getElementById('origin').value;
    var destination = document.getElementById('destination').value;

    if (origin && destination) {
        console.log('Calculating distance between', origin, 'and', destination);
        var service = new google.maps.DistanceMatrixService();
        service.getDistanceMatrix({
            origins: [origin],
            destinations: [destination],
            travelMode: 'DRIVING',
            unitSystem: google.maps.UnitSystem.IMPERIAL,
        }, function(response, status) {
            if (status === 'OK') {
                var distance = response.rows[0].elements[0].distance;
                if (distance) {
                    console.log('Distance calculated:', distance.text);
                    document.getElementById('miles').value = (distance.value / 1609.34).toFixed(1); // Convert meters to miles
                }
            } else {
                console.error('Error calculating distance:', status);
            }
        });
    } else {
        console.warn('Origin or destination is empty');
    }
}

function updateMiles(checked) {
    const milesInput = document.getElementById('miles');
    if (checked) {
        milesInput.value = (parseFloat(milesInput.value) * 2).toFixed(1);
    } else {
        milesInput.value = (parseFloat(milesInput.value) / 2).toFixed(1);
    }
    console.log('Miles updated to', milesInput.value);
}

function switchInputs() {
    console.log('Switch button clicked'); // Debugging statement
    const originInput = document.getElementById('origin');
    const destinationInput = document.getElementById('destination');

    if (originInput && destinationInput) {
        console.log('Switching inputs in the DOM'); // Debugging statement

        // Swap the input elements
        const originParent = originInput.closest('.input-group');
        const destinationParent = destinationInput.closest('.input-group');

        // Create placeholders to hold the positions
        const originPlaceholder = document.createElement('div');
        const destinationPlaceholder = document.createElement('div');

        originParent.insertBefore(originPlaceholder, originInput);
        destinationParent.insertBefore(destinationPlaceholder, destinationInput);

        // Move the elements
        originParent.insertBefore(destinationInput, originPlaceholder);
        destinationParent.insertBefore(originInput, destinationPlaceholder);

        // Remove placeholders
        originPlaceholder.remove();
        destinationPlaceholder.remove();

        // Ensure the IDs and names remain the same
        originInput.id = 'destination';
        originInput.name = 'destination';
        destinationInput.id = 'origin';
        destinationInput.name = 'origin';

        console.log('Inputs and IDs/names switched successfully');

        // Re-initialize the autocomplete for the switched inputs
        initAutocomplete();

        // Re-initialize select2 if the destination is a select element
        if (destinationInput instanceof HTMLSelectElement) {
            $(destinationInput).select2();
        }
        if (originInput instanceof HTMLSelectElement) {
            $(originInput).select2();
        }
    } else {
        console.error('Origin or Destination input not found');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof google === 'object' && typeof google.maps === 'object') {
        console.log('Google Maps API loaded');
        initAutocomplete();
    } else {
        console.error('Google Maps API not loaded');
    }
});