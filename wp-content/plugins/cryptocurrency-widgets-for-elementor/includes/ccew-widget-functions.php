<?php

/**
 * Inset data in Database
 */
function ccew_widget_coin_peprika_insert_data()
{
    $update_api_name = 'ccew-active-api';
    $data_cache_name = 'ccew-saved-coindata';
    $activate_api = get_transient($update_api_name);
    $cache = get_transient($data_cache_name);

    //$api_option = get_option("ccew-api-settings");
    $api_option = get_option('openexchange-api-settings');
    $cache_time = isset($api_option['select_cache_time']) ? (int) $api_option['select_cache_time'] : 10;

    // Avoid updating database if cache exist and same API is requested
    if ($activate_api == 'CoinPaprika' && false != $cache) {
        return;
    }

    $api_url = 'https://api.coinpaprika.com/v1/tickers';
    $request = wp_remote_get(
        $api_url,
        array(
            'timeout' => 120,
            'sslverify' => false,
        )
    );
    if (is_wp_error($request)) {
        return false; // Bail early
    }
    $body = wp_remote_retrieve_body($request);
    $coin_info = json_decode($body, true);
    $response = array();
    $coin_data = array();
    $coin_info = array_slice($coin_info, 0, 200);

    if (is_array($coin_info) && !empty($coin_info)) {
        foreach ($coin_info as $coin) {
            if (contains_emoji($coin->name) || contains_emoji($coin->symbol) ||  contains_emoji($coin->id)) {
                continue;
            }
            $response['coin_id'] = ccew_coin_array($coin['id']);
            $response['rank'] = $coin['rank'];
            $response['name'] = $coin['name'];
            $response['symbol'] = strtoupper($coin['symbol']);
            $response['price'] = ccew_set_default_if_empty($coin['quotes']['USD']['price'], 0.00);
            $response['percent_change_1h'] = ccew_set_default_if_empty($coin['quotes']['USD']['percent_change_1h']);
            $response['percent_change_24h'] = ccew_set_default_if_empty($coin['quotes']['USD']['percent_change_24h']);
            $response['percent_change_7d'] = ccew_set_default_if_empty($coin['quotes']['USD']['percent_change_7d']);
            $response['percent_change_30d'] = ccew_set_default_if_empty($coin['quotes']['USD']['percent_change_30d']);
            //   $response['high_24h'] = 'N/A';
            //  $response['low_24h'] = 'N/A';
            $response['market_cap'] = ccew_set_default_if_empty($coin['quotes']['USD']['market_cap'], 0);
            //  $response['total_volume'] = 'N/A';
            $response['total_supply'] = ccew_set_default_if_empty($coin['total_supply']);
            // $response['circulating_supply'] = ccew_set_default_if_empty($coin['circulating_supply']);
            // $response['7d_chart'] = 'N/A';
            //  $response['logo'] = 'N/A';
            $response['coin_last_update'] = gmdate('Y-m-d h:i:s');
            $coin_data[] = $response;

            // Save data in chunks of 50 to avoid memory issues
            if (count($coin_data) >= 50) {
                save_coin_data($coin_data);
                $coin_data = array(); // Reset array for next chunk
            }
        }

        // Save any remaining data that was not saved in the last chunk
        if (!empty($coin_data)) {
            save_coin_data($coin_data);
        }

        // $DB = new ccew_database();
        // $DB->ccew_insert($coin_data);
        set_transient($data_cache_name, date('H:s:i'), $cache_time * MINUTE_IN_SECONDS);
        set_transient($update_api_name, 'CoinPaprika', 0);
        return true;
    }
}

/**
 * Insert data in Database using Coingecko
 */
function ccew_widget_insert_data()
{
    $update_api_name = 'ccew-active-api';
    $data_cache_name = 'ccew-saved-coindata';
    $activate_api = get_transient($update_api_name);
    $cache = get_transient($data_cache_name);

    $api_option1 = get_option('openexchange-api-settings');
    $coingecko_api_key = (isset($api_option1['coingecko_api'])) ? $api_option1['coingecko_api'] : "";
    $coingecko_api_cache_time = isset($api_option1['select_cache_time']) ? (int) $api_option1['select_cache_time'] : 10;

    if (!ccew_check_user()) {
        return;
    }

    // Avoid updating database if cache exist and same API is requested
    if ($activate_api == 'CoinGecko' && false != $cache) {
        return;
    }

    $coin_info = array();
    $api_url = 'https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&order=market_cap_desc&per_page=200&page=1&sparkline=true&price_change_percentage=1h%2C24h%2C7d%2C30d&x_cg_demo_api_key=' . $coingecko_api_key;
    $request = wp_remote_get(
        $api_url,
        array(
            'timeout' => 120,
            'sslverify' => false,
        )
    );
    if (is_wp_error($request)) {
        return false; // Bail early
    }
    $body = wp_remote_retrieve_body($request);
    $coin_info = json_decode($body);
    $response = array();
    $coin_data = array();
    if (is_array($coin_info) && !empty($coin_info)) {
        ccew_track_coingecko_api_hit();
        foreach ($coin_info as $coin) {
            if (contains_emoji($coin->name) || contains_emoji($coin->symbol) ||  contains_emoji($coin->id)) {
                continue;
            }
            $response['coin_id'] = $coin->id;
            $response['rank'] = $coin->market_cap_rank;
            $response['name'] = $coin->name;
            $response['symbol'] = strtoupper($coin->symbol);
            $response['price'] = ccew_set_default_if_empty($coin->current_price, 0.00);
            $response['percent_change_1h'] = ccew_set_default_if_empty($coin->price_change_percentage_1h_in_currency);
            $response['percent_change_24h'] = ccew_set_default_if_empty($coin->price_change_percentage_24h_in_currency);
            $response['percent_change_7d'] = ccew_set_default_if_empty($coin->price_change_percentage_7d_in_currency);
            $response['percent_change_30d'] = ccew_set_default_if_empty($coin->price_change_percentage_30d_in_currency);
            $response['high_24h'] = ccew_set_default_if_empty($coin->high_24h);
            $response['low_24h'] = ccew_set_default_if_empty($coin->low_24h);
            $response['market_cap'] = ccew_set_default_if_empty($coin->market_cap, 0);
            $response['total_volume'] = ccew_set_default_if_empty($coin->total_volume);
            $response['total_supply'] = ccew_set_default_if_empty($coin->total_supply);
            $response['circulating_supply'] = ccew_set_default_if_empty($coin->circulating_supply);
            $response['7d_chart'] = json_encode($coin->sparkline_in_7d->price);
            $response['logo'] = $coin->image;
            $response['coin_last_update'] = gmdate('Y-m-d h:i:s');
            $coin_data[] = $response;

            // Save data in chunks of 50 to avoid memory issues
            if (count($coin_data) >= 50) {
                save_coin_data($coin_data);
                $coin_data = array(); // Reset array for next chunk
            }
        }
        // Save any remaining data that was not saved in the last chunk
        if (!empty($coin_data)) {
            save_coin_data($coin_data);
        }
        set_transient($data_cache_name, date('H:s:i'), $coingecko_api_cache_time * MINUTE_IN_SECONDS);
        set_transient($update_api_name, 'CoinGecko', 0);
        return true;
    }
}

/**
 * Single coin update
 */
function ccew_single_coin_peprika_update($coin_id)
{

    $api_url = 'https://api.coinpaprika.com/v1/tickers/' . $coin_id;
    $request = wp_remote_get(
        $api_url,
        array(
            'timeout' => 120,
            'sslverify' => false,
        )
    );
    if (is_wp_error($request)) {
        return false; // Bail early
    }
    $body = wp_remote_retrieve_body($request);
    $coin_info = json_decode($body, true);
    $response = array();
    $coin_data = array();
    if (is_array($coin_info) && !empty($coin_info)) {
        $coin = $coin_info;
        $response['coin_id'] = ccew_coin_array($coin['id']);
        $response['rank'] = $coin['rank'];
        $response['name'] = $coin['name'];
        $response['symbol'] = strtoupper($coin['symbol']);
        $response['price'] = ccew_set_default_if_empty($coin['quotes']['USD']['price'], 0.00);
        $response['percent_change_1h'] = ccew_set_default_if_empty($coin['quotes']['USD']['percent_change_1h']);
        $response['percent_change_24h'] = ccew_set_default_if_empty($coin['quotes']['USD']['percent_change_24h']);
        $response['percent_change_7d'] = ccew_set_default_if_empty($coin['quotes']['USD']['percent_change_7d']);
        $response['percent_change_30d'] = ccew_set_default_if_empty($coin['quotes']['USD']['percent_change_30d']);
        //   $response['high_24h'] = 'N/A';
        //  $response['low_24h'] = 'N/A';
        $response['market_cap'] = ccew_set_default_if_empty($coin['quotes']['USD']['market_cap'], 0);
        //  $response['total_volume'] = 'N/A';
        $response['total_supply'] = ccew_set_default_if_empty($coin['total_supply']);
        // $response['circulating_supply'] = ccew_set_default_if_empty($coin['circulating_supply']);
        $response['7d_chart'] = json_encode(ccew_coin_peprik_historical_data($coin_id));
        //  $response['logo'] = 'N/A';
        $response['coin_last_update'] = gmdate('Y-m-d h:i:s');
        $coin_data[] = $response;

        $DB = new ccew_database();
        $response_save = $DB->ccew_insert($coin_data);
        return ccew_coin_data_return(ccew_coin_array($coin_id));
    }
}
function ccew_save_chart7day($coin_id)
{

    $response['coin_id'] = ccew_coin_array($coin_id);

    $response['7d_chart'] = json_encode(ccew_coin_peprik_historical_data($coin_id));

    $coin_data[] = $response;

    $DB = new ccew_database();
    $response_save = $DB->ccew_insert($coin_data);
    return $response['7d_chart'];

}

/**
 * track coingecko api hits
 */
function ccew_track_coingecko_api_hit()
{
    $api_hits = get_option('cmc_coingecko_api_hits');
    if ($api_hits === false) {
        // Option doesn't exist, so initialize it with a value of 0
        add_option('cmc_coingecko_api_hits', 0);
    }
    $api_hits = (int) $api_hits + 1; // Increment the value by 1
    update_option('cmc_coingecko_api_hits', $api_hits);
}

/**
 * check user is new or old
 */

function ccew_check_user()
{

    $fresh_install = get_option('ccew-fresh-installation');

    $api_option = get_option("openexchange-api-settings");
    $coingecko_api_key = (isset($api_option['coingecko_api'])) ? $api_option['coingecko_api'] : "";
    $coingecko_api_key = ($fresh_install) ? $coingecko_api_key : 'true';
    if (!empty($coingecko_api_key)) {
        return true;
    } else {
        return false;
    }
}

function ccew_coin_peprik_historical_data($coin_id)
{

    // $transient_name = "ccew_7day_chart_" . $coin_id;
    $coin_data = "";

    // if (empty($coin_data) || $coin_data == false) {
    $seven_day = strtotime("-2 week");
    $api_url = 'https://api.coinpaprika.com/v1/tickers/' . $coin_id . '/historical?start=' . $seven_day . '&interval=1d';
    $request = wp_remote_get(
        $api_url,
        array(
            'timeout' => 120,
            'sslverify' => false,
        )
    );
    if (is_wp_error($request)) {
        return false; // Bail early
    }
    $body = wp_remote_retrieve_body($request);
    $coin_info = json_decode($body, true);
    $coin_data = array();

    if (is_array($coin_info) && !empty($coin_info)) {
        foreach ($coin_info as $coin => $value) {
            $coin_data[] = $value['price'];

        }

    }

    //  set_transient($transient_name, $coin_data, 24 * HOUR_IN_SECONDS);
    return $coin_data;

    //}

    //return $coin_data;

}

function ccew_single_coin_update($coin_id)
{
    $api_option1 = get_option('openexchange-api-settings');
    $coingecko_api_key = (isset($api_option1['coingecko_api'])) ? $api_option1['coingecko_api'] : "";

    $api_url = 'https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&ids=' . $coin_id . '&order=market_cap_desc&per_page=100&page=1&sparkline=true&price_change_percentage=1h%2C24h%2C7d%2C30d&x_cg_demo_api_key=' . $coingecko_api_key;
    $request = wp_remote_get(
        $api_url,
        array(
            'timeout' => 120,
            'sslverify' => false,
        )
    );
    if (is_wp_error($request)) {
        return false; // Bail early
    }
    $body = wp_remote_retrieve_body($request);
    $coin_info = json_decode($body);
    $response = array();
    $coin_data = array();
    if (is_array($coin_info) && !empty($coin_info)) {
        ccew_track_coingecko_api_hit();
        $coin = $coin_info[0];
        $response['coin_id'] = $coin->id;
        $response['rank'] = $coin->market_cap_rank;
        $response['name'] = $coin->name;
        $response['symbol'] = strtoupper($coin->symbol);
        $response['price'] = ccew_set_default_if_empty($coin->current_price, 0.00);
        $response['percent_change_1h'] = ccew_set_default_if_empty($coin->price_change_percentage_1h_in_currency);
        $response['percent_change_24h'] = ccew_set_default_if_empty($coin->price_change_percentage_24h_in_currency);
        $response['percent_change_7d'] = ccew_set_default_if_empty($coin->price_change_percentage_7d_in_currency);
        $response['percent_change_30d'] = ccew_set_default_if_empty($coin->price_change_percentage_30d_in_currency);
        $response['high_24h'] = ccew_set_default_if_empty($coin->high_24h);
        $response['low_24h'] = ccew_set_default_if_empty($coin->low_24h);
        $response['market_cap'] = ccew_set_default_if_empty($coin->market_cap, 0);
        $response['total_volume'] = ccew_set_default_if_empty($coin->total_volume);
        $response['total_supply'] = ccew_set_default_if_empty($coin->total_supply);
        $response['circulating_supply'] = ccew_set_default_if_empty($coin->circulating_supply);
        $response['7d_chart'] = json_encode($coin->sparkline_in_7d->price);
        $response['logo'] = $coin->image;
        $response['coin_last_update'] = gmdate('Y-m-d h:i:s');
        $coin_data[] = $response;
        $DB = new ccew_database();
        $DB->ccew_insert($coin_data);
        return ccew_coin_data_return($coin_id);
    }
}

/**
 * Return coin  data for card and label widget
 */
function ccew_coin_data_return($coin_id)
{
    $DB = new ccew_database();
    $coin_info = $DB->get_coins(array('coin_id' => $coin_id));
    return $coin_info[0];
}

function convert_24points($points)
{
    $charts = array();
    $charts = array_slice($points, -24);

    return json_encode($charts);
}

/**
 * Check coin exist or not in database
 * Check last update of coin
 */
function ccew_widget_get_coin_data($coin_id)
{
    if ($coin_id === '') {
        return false;
    }
    $DB = new ccew_database();
    $coin_data_available = $DB->coin_exists_by_id($coin_id);
    if ($coin_data_available == true) {
        $updated = $DB->check_coin_latest_update($coin_id);
        if ($updated == true) {
            return ccew_coin_data_return($coin_id);
        } else {
            $api = get_option('ccew-api-settings');
            $api = (!isset($api['select_api']) && empty($api['select_api'])) ? "coin_gecko" : $api['select_api'];
            $api_data = ($api == "coin_gecko") ? ccew_single_coin_update($coin_id) : ccew_single_coin_peprika_update(ccew_coin_array($coin_id, true));

            return $api_data;

        }
    } else {
        $api = get_option('ccew-api-settings');
        $api = (!isset($api['select_api']) && empty($api['select_api'])) ? "coin_gecko" : $api['select_api'];
        $api_data = ($api == "coin_gecko") ? ccew_single_coin_update($coin_id) : ccew_single_coin_peprika_update(ccew_coin_array($coin_id, true));

        return $api_data;

    }
}

/**
 * Return coin data for list widget
 */

function ccew_widget_get_list_data($numberof_coins, $sortby)
{
    $cache = get_transient('ccew-saved-coindata');
    $coin_data = array();
    $data = '';
    // Updating database if cache is not available
    if (false == $cache) {
        $api = get_option('ccew-api-settings');
        $api = (!isset($api['select_api']) && empty($api['select_api'])) ? "coin_gecko" : $api['select_api'];

        $data = ($api == "coin_gecko") ? ccew_widget_insert_data() : ccew_widget_coin_peprika_insert_data();

    }
    $DB = new ccew_database();
    if ($sortby == 'gainer') {
        $coins = $DB->get_coins(
            array(
                'number' => $numberof_coins,
                'order' => 'DESC',
                'orderby' => 'percent_change_24h',
            )
        );
        foreach ($coins as $coin) {
            $coin = ccew_objectToArray($coin);
            if ($coin['percent_change_24h'] >= 0) {
                $coin_data[] = $coin;
            }
        }
    } elseif ($sortby == 'loser') {
        $coins = $DB->get_coins(
            array(
                'number' => $numberof_coins,
                'order' => 'ASC',
                'orderby' => 'percent_change_24h',
            )
        );
        foreach ($coins as $coin) {
            $coin = ccew_objectToArray($coin);
            if ($coin['percent_change_24h'] < 0) {
                $coin_data[] = $coin;
            }
        }
    } else {
        if (is_array($numberof_coins)) {
            foreach ($numberof_coins as $coin_id) {
                $coins = $DB->get_coins(array('coin_id' => $coin_id));
                $coin = ccew_objectToArray($coins[0]);
                $coin_data[] = $coin;
            }
        } elseif (empty($numberof_coins)) {
            $coin_data['empty'] = 'empty';
        } else {
            $coins = $DB->get_coins(
                array(
                    'number' => $numberof_coins,
                    'order' => 'DESC',
                    'orderby' => 'market_cap',
                )
            );
            foreach ($coins as $coin) {
                $coin = ccew_objectToArray($coin);
                $coin_data[] = $coin;
            }
        }
    }
    if ($data === false) {
        if ($coin_data == null || $coin_info[0] == false) {
            return false;
        } else {
            return $coin_data;
        }
    } else {
        return $coin_data;
    }
}

function ccew_get_table_data($data_length, $startpoint, $numberof_coins, $order_col_name, $order_type)
{

    $cache_data = get_transient('ccew-saved-coindata');
    // Updating database if cache is not available
    if (false == $cache_data) {
        $api = get_option('ccew-api-settings');
        $api = (!isset($api['select_api']) && empty($api['select_api'])) ? "coin_gecko" : $api['select_api'];
        $data_update = ($api == "coin_gecko") ? ccew_widget_insert_data() : ccew_widget_coin_peprika_insert_data();
    }
    $DB = new ccew_database();
    if (is_array($numberof_coins)) {
        $coin_data = $DB->get_coins(
            array(
                'coin_id' => $numberof_coins,
                'offset' => $startpoint,
                'number' => $data_length,
                'orderby' => $order_col_name,
                'order' => $order_type,
            )
        );
    } else {
        $coin_data = $DB->get_coins(
            array(
                'number' => $data_length,
                'offset' => $startpoint,
                'orderby' => $order_col_name,
                'order' => $order_type,
            )
        );
    }
    return $coin_data;
}

function ccew_changes_up_down($value)
{
    $change_class = 'up';
    $change_sign = '<i class="ccew_icon-up" aria-hidden="true"></i>';
    $change_sign_minus = '-';
    $changes_html = '';
    if (strpos($value, $change_sign_minus) !== false) {
        $change_sign = '<i class="ccew_icon-down" aria-hidden="true"></i>';
        $change_class = 'down';
    }
    $changes_html = '<span class="changes ' . esc_attr($change_class) . '">' . $change_sign . esc_html($value) . '</span>';
    return $changes_html;
}

/**
 * Check if provided $value is empty or not.
 * Return $default if $value is empty
 */
function ccew_set_default_if_empty($value, $default = 'N/A')
{
    return $value ? $value : $default;
}

/**
 * Check coin logo availbale in database or local
 * Return coin logo
 */
function ccew_get_coin_logo($coin_id, $size = 32, $HTML = true)
{
    $logo_html = '';
    //$DB = new ccew_database();
    $coin_icon = ccew_coin_list_logos_default($coin_id);
    $logo_html = '<img id="' . esc_attr($coin_id) . '" alt="' . esc_attr($coin_id) . '" src="' . esc_url($coin_icon) . '" onerror="this.src = \'https://res.cloudinary.com/pinkborder/image/upload/coinmarketcap-coolplugins/128x128/default-logo.png\';">';

    return $logo_html;
}

// currencies symbol
function ccew_currency_symbol($name)
{
    $cc = strtoupper($name);
    $currency = array(
        'USD' => '&#36;', // U.S. Dollar
        'CLP' => '&#36;', // CLP Dollar
        'SGD' => 'S&#36;', // Singapur dollar
        'AUD' => '&#36;', // Australian Dollar
        'BRL' => 'R&#36;', // Brazilian Real
        'CAD' => 'C&#36;', // Canadian Dollar
        'CZK' => 'K&#269;', // Czech Koruna
        'DKK' => 'kr', // Danish Krone
        'EUR' => '&euro;', // Euro
        'HKD' => '&#36', // Hong Kong Dollar
        'HUF' => 'Ft', // Hungarian Forint
        'ILS' => '&#x20aa;', // Israeli New Sheqel
        'INR' => '&#8377;', // Indian Rupee
        'IDR' => 'Rp', // Indian Rupee
        'KRW' => '&#8361;', // WON
        'CNY' => '&#165;', // CNY
        'JPY' => '&yen;', // Japanese Yen
        'MYR' => 'RM', // Malaysian Ringgit
        'MXN' => '&#36;', // Mexican Peso
        'NOK' => 'kr', // Norwegian Krone
        'NZD' => '&#36;', // New Zealand Dollar
        'PHP' => '&#x20b1;', // Philippine Peso
        'PLN' => '&#122;&#322;', // Polish Zloty
        'GBP' => '&pound;', // Pound Sterling
        'SEK' => 'kr', // Swedish Krona
        'CHF' => 'Fr', // Swiss Franc
        'TWD' => 'NT&#36;', // Taiwan New Dollar
        'PKR' => 'Rs', // Rs
        'THB' => '&#3647;', // Thai Baht
        'TRY' => '&#8378;', // Turkish Lira
        'ZAR' => 'R', // zar
        'RUB' => '&#8381;', // rub
    );

    if (array_key_exists($cc, $currency)) {
        return $currency[$cc];
    }
}

/**
 * Formating of coin
 */
function ccew_value_format_number($n)
{
    if (!is_numeric($n)) {
        return 'Invalid Value';
    }

    if ($n <= 0.00001 && $n > 0) {
        return $formatted = number_format($n, 8, '.', ',');
    } elseif ($n <= 0.0001 && $n > 0.00001) {
        return $formatted = number_format($n, 8, '.', ',');
    } elseif ($n <= 0.001 && $n > 0.0001) {
        return $formatted = number_format($n, 5, '.', ',');
    } elseif ($n <= 0.01 && $n > 0.001) {
        return $formatted = number_format($n, 4, '.', ',');
    } elseif ($n < 1 && $n > 0.01) {
        return $formatted = number_format($n, 4, '.', ',');
    } else {
        return $formatted = number_format($n, 2, '.', ',');
    }
}

function ccew_format_coin_value($value, $precision = 2)
{

    if ($value < 1000000) {
        // Anything less than a million
        $formated_str = number_format($value, $precision);
    } elseif ($value < 1000000000) {
        // Anything less than a billion
        $formated_str = number_format($value / 1000000, $precision) . 'M';
    } else {
        // At least a billion
        $formated_str = number_format($value / 1000000000, $precision) . 'B';
    }

    return $formated_str;
}

function ccew_widget_format_coin_value($value, $precision = 2)
{
    if (!is_numeric($value)) {
        return 'Invalid Value';
    }

    if ($value < 1000000) {
        // Anything less than a million
        $formatted_str = number_format($value, $precision);
    } elseif ($value < 1000000000) {
        // Anything less than a billion
        $formatted_str = number_format($value / 1000000, $precision) . ' Million';
    } else {
        // At least a billion
        $formatted_str = number_format($value / 1000000000, $precision) . ' Billion';
    }

    return $formatted_str;
}

/* USD conversions */
function ccew_usd_conversions($currency)
{
    // use common transient between cmc and ccpw
    $conversions = get_transient('cmc_usd_conversions');
    $conversions_option = get_option('cmc_usd_conversions');

    if (empty($conversions) || $conversions === '' || empty($conversions_option)) {
        $api_option = get_option('openexchange-api-settings');
        $api = (!empty($api_option['openexchangerate_api'])) ? $api_option['openexchangerate_api'] : '';
        $request = '';
        if (empty($api)) {
            if (!empty($conversions_option)) {
                if ($currency == 'all') {
                    return $conversions_option;
                } else {
                    if (isset($conversions_option[$currency])) {
                        return $conversions_option[$currency];
                    }
                }
            }
            return false;
        } else {
            $request = wp_remote_get(
                'https://openexchangerates.org/api/latest.json?app_id=' . $api . '',
                array(
                    'timeout' => 120,
                    'sslverify' => true,
                )
            );
        }

        if (is_wp_error($request)) {
            return false;
        }

        $currency_ids = array('USD', 'AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'INR', 'JPY', 'MYR', 'MXN', 'NOK', 'NZD', 'PHP', 'PLN', 'GBP', 'SEK', 'CHF', 'TWD', 'THB', 'TRY', 'CNY', 'KRW', 'RUB', 'SGD', 'CLP', 'IDR', 'PKR', 'ZAR');
        $body = wp_remote_retrieve_body($request);
        $conversion_data = json_decode($body);

        if (isset($conversion_data->rates)) {
            $conversion_data = (array) $conversion_data->rates;
        } else {
            $conversion_data = array();
            if (!empty($conversions_option)) {
                if ($currency == 'all') {
                    return $conversions_option;
                } else {
                    if (isset($conversions_option[$currency])) {
                        return $conversions_option[$currency];
                    }
                }
            }
        }

        if (is_array($conversion_data) && count($conversion_data) > 0) {
            foreach ($conversion_data as $key => $currency_price) {
                if (in_array($key, $currency_ids)) {
                    $conversions_option[$key] = $currency_price;
                }
            }

            uksort(
                $conversions_option,
                function ($key1, $key2) use ($currency_ids) {
                    return (array_search($key1, $currency_ids) > array_search($key2, $currency_ids)) ? 1 : -1;
                }
            );

            update_option('cmc_usd_conversions', $conversions_option);
            set_transient('cmc_usd_conversions', $conversions_option, 12 * HOUR_IN_SECONDS);
        }
    }

    if ($currency == 'all') {
        return $conversions_option;
    } else {
        if (isset($conversions_option[$currency])) {
            return $conversions_option[$currency];
        }
    }
}

/**
 * List of Coin Ids
 */
function ccew_get_all_coin_ids()
{
    $DB = new ccew_database();
    $coin_data = $DB->get_coins(array('number' => '1000'));
    $coin_data = ccew_objectToArray($coin_data);
    if (is_array($coin_data) && isset($coin_data) && $coin_data != null) {

        $coins = array();
        foreach ($coin_data as $coin) {
            $coins[$coin['coin_id']] = $coin['name'];
        }
        return $coins;
    } else {
        $not['not'] = __('Coin Not Available', 'ccew');
        return $not;
    }
}
// object to array conversion
function ccew_objectToArray($d)
{
    if (is_object($d)) {
        // Gets the properties of the given object
        // with get_object_vars function
        $d = get_object_vars($d);
    }
    if (is_array($d)) {
        /*
         * Return array converted to object
         * Using __FUNCTION__ (Magic constant)
         * for recursive call
         */
        return array_map(__FUNCTION__, $d);
    } else {
        // Return array
        return $d;
    }
}

/*
|--------------------------------------------------------------------------
| generating coin logo URL based upon coin id
|--------------------------------------------------------------------------
 */
function ccew_coin_list_logos_default($coin_id, $size = 32)
{
    $logo_html = '';
    $coin_logo_info = array();
    $coin_svg = CCEW_DIR . '/assets/images/logos/' . $coin_id . '.svg';
    $coin_pngs = CCEW_DIR . '/assets/images/logos/' . $coin_id . '.png';
    if (file_exists($coin_svg)) {
        return $logo_path = CCEW_URL . 'assets/images/logos/' . $coin_id . '.svg';
    } else if (file_exists($coin_pngs)) {
        return $logo_path = CCEW_URL . 'assets/images/logos/' . $coin_id . '.png';
    } else {
        $api = get_option('ccew-api-settings');
        $api = (!isset($api['select_api']) && empty($api['select_api'])) ? "coin_gecko" : $api['select_api'];
        if ($api == "coin_gecko") {
            $DB = new ccew_database();
            $coin_icon = $DB->get_coin_logo($coin_id);
            return $coin_icon;

        } else if ($api == "coin_paprika") {
            return 'https://static.coinpaprika.com/coin/' . ccew_coin_array($coin_id, true) . '/logo.png';

        }

        return $logo_path = CCEW_URL . 'assets/images/default-logo.png';

    }
}

//coins ids
function ccew_coin_array($coin_id, $flip = false)
{
    $json_data = file_get_contents(CCEW_DIR . 'assets/ccew-coins-ids.json');
    // Decode the JSON data into an associative array
    $coin_list = json_decode($json_data, true);

    if ($flip == true) {
        $fliped_array = array_flip($coin_list);
        return (isset($fliped_array[$coin_id])) ? $fliped_array[$coin_id] : $coin_id;
    } else {
        return (isset($coin_list[$coin_id])) ? $coin_list[$coin_id] : $coin_id;

    }

}

/**
* Check if a string contains emoji.
*
* @param string $string The string to check.
* @return bool True if the string contains emoji, false otherwise.
*/
function contains_emoji($string)
{
    return preg_match('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', $string) > 0;
}

/**
* Save coin data to the database.
*
* @param array $coin_data The coin data to save.
*/
function save_coin_data($coin_data)
{
    $DB = new ccew_database();
    $DB->ccew_insert($coin_data);
}
