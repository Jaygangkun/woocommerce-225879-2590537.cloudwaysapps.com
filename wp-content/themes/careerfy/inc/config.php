<?php

/**
 * Careerfy Theme Config.
 *
 * @package Careerfy
 */
define("CAREERFY_VERSION", "7.7.0");
define("WP_JOBSEARCH_VERSION", "1.8.8");

function careerfy_framework_options() {
    global $careerfy_framework_options;
    if (empty($careerfy_framework_options)) {
        $careerfy_framework_options = get_option('careerfy_framework_options');
    }
    return $careerfy_framework_options;
}