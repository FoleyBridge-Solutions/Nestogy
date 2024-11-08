
<div class="modal" id="addInventoryLocationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-fw fa-cart-plus mr-2"></i>New Inventory Location</h5>
                <button type="button" class="close text-white" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body bg-white">
                <div class="form-group">
                    <label for="location_name">Location Name</label>
                    <input type="text" class="form-control" id="location_name" name="location_name" required>
                </div>
                <div class="form-group">
                    <label for="location_description">Location Description</label>
                    <textarea class="form-control" id="location_description" name="location_description" rows="2"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="location_address">Location Address</label>
                            <input type="text" class="form-control" id="location_address" name="location_address">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="location_city">Location City</label>
                            <input type="text" class="form-control" id="location_city" name="location_city">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="location_state">Location State</label>
                            <select class="form-control select2" id="location_state" name="location_state">
                                <option value="">- State -</option>
                                <?php
                                $states = [
                                    'AL' => 'Alabama',
                                    'AK' => 'Alaska',
                                    'AZ' => 'Arizona',
                                    'AR' => 'Arkansas',
                                    'CA' => 'California',
                                    'CO' => 'Colorado',
                                    'CT' => 'Connecticut',
                                    'DE' => 'Delaware',
                                    'DC' => 'District of Columbia',
                                    'FL' => 'Florida',
                                    'GA' => 'Georgia',
                                    'HI' => 'Hawaii',
                                    'ID' => 'Idaho',
                                    'IL' => 'Illinois',
                                    'IN' => 'Indiana',
                                    'IA' => 'Iowa',
                                    'KS' => 'Kansas',
                                    'KY' => 'Kentucky',
                                    'LA' => 'Louisiana',
                                    'ME' => 'Maine',
                                    'MD' => 'Maryland',
                                    'MA' => 'Massachusetts',
                                    'MI' => 'Michigan',
                                    'MN' => 'Minnesota',
                                    'MS' => 'Mississippi',
                                    'MO' => 'Missouri',
                                    'MT' => 'Montana',
                                    'NE' => 'Nebraska',
                                    'NV' => 'Nevada',
                                    'NH' => 'New Hampshire',
                                    'NJ' => 'New Jersey',
                                    'NM' => 'New Mexico',
                                    'NY' => 'New York',
                                    'NC' => 'North Carolina',
                                    'ND' => 'North Dakota',
                                    'OH' => 'Ohio',
                                    'OK' => 'Oklahoma',
                                    'OR' => 'Oregon',
                                    'PA' => 'Pennsylvania',
                                    'RI' => 'Rhode Island',
                                    'SC' => 'South Carolina',
                                    'SD' => 'South Dakota',
                                    'TN' => 'Tennessee',
                                    'TX' => 'Texas',
                                    'UT' => 'Utah',
                                    'VT' => 'Vermont',
                                    'VA' => 'Virginia',
                                    'WA' => 'Washington',
                                    'WV' => 'West Virginia',
                                    'WI' => 'Wisconsin',
                                    'WY' => 'Wyoming',
                                    'PR' => 'Puerto Rico',
                                    'VI' => 'Virgin Islands',
                                    'GU' => 'Guam',
                                    'AS' => 'American Samoa',
                                    'MP' => 'Northern Mariana Islands',
                                    'FM' => 'Micronesia',
                                    'MH' => 'Marshall Islands',
                                    'PW' => 'Palau',
                                    'UM' => 'United States Minor Outlying Islands',
                                    'UZ' => 'U.S. Virgin Islands',
                                    'US' => 'Other United States',
                                ];
                                foreach ($states as $state_code => $state_name) {
                                    echo '<option value="' . $state_code . '">' . $state_name . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="location_zip">Location Zip</label>
                            <input type="text" class="form-control" id="location_zip" name="location_zip">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" name="inventory_location_add">
                    <i class="fa fa-fw fa-plus"></i>
                    Add Location
                </button>
            </div>
        </div>
    </div>
</div>

<?php