<?php

// Mock WP core classes and functions
class WP_Error
{
    public $code, $message, $data;
    public function __construct($code = '', $message = '', $data = '')
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }
}

class WP_REST_Server
{
    const READABLE = 'GET';
}

$wp_options = [];
$wp_transients = [];
$wp_remote_responses = [];

function get_option($key, $default_value = false)
{
    global $wp_options;
    return isset($wp_options[$key]) ? $wp_options[$key] : $default_value;
}

function update_option($key, $value)
{
    global $wp_options;
    $wp_options[$key] = $value;
}

function get_transient($key)
{
    global $wp_transients;
    return isset($wp_transients[$key]) ? $wp_transients[$key] : false;
}

function set_transient($key, $value, $expiration = 0)
{
    global $wp_transients;
    $wp_transients[$key] = $value;
}

function wp_remote_get($url, $args = [])
{
    global $wp_remote_responses;
    $wp_remote_responses[] = ['url' => $url, 'args' => $args];

    // Mock response
    return [
        'response' => ['code' => 200],
        'body' => json_encode(['items' => [['name' => 'Mock Org']]])
    ];
}

function is_wp_error($thing)
{
    return $thing instanceof WP_Error;
}

function wp_remote_retrieve_response_code($response)
{
    return $response['response']['code'] ?? 500;
}

function wp_remote_retrieve_body($response)
{
    return $response['body'] ?? '';
}

function add_query_arg($args, $url)
{
    $query = http_build_query($args);
    $separator = strpos($url, '?') === false ? '?' : '&';
    return $url . $separator . $query;
}

function add_action($hook, $callback, $priority = 10, $accepted_args = 1)
{
}
function add_shortcode($tag, $callback)
{
}
function home_url($path = '', $scheme = null)
{
    return 'https://test.local' . $path;
}
function wp_kses_post($data)
{
    return $data;
}
function esc_html($text)
{
    return htmlspecialchars($text);
}
function esc_url($url)
{
    return $url;
}
function esc_attr($str)
{
    return htmlspecialchars($str);
}
function sanitize_text_field($str)
{
    return strip_tags($str);
}
function wp_unslash($str)
{
    return stripslashes($str);
}
function register_block_bindings_source($source_name, $source_properties)
{
}
function shortcode_atts($pairs, $atts, $shortcode = '')
{
    return array_merge($pairs, $atts ?? array());
}
function sanitize_title($title)
{
    return strtolower(str_replace(' ', '-', $title));
}
function __($text, $domain = 'default')
{
    return $text;
}
function esc_html_e($text, $domain = 'default')
{
    echo htmlspecialchars($text);
}
function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all')
{
}

class WP_REST_Request
{
    public $method, $route, $query_params = array();
    public function __construct($method, $route)
    {
        $this->method = $method;
        $this->route = $route;
    }
    public function set_query_params($params)
    {
        $this->query_params = $params;
    }
    public function get_query_params()
    {
        return $this->query_params;
    }
}

class WP_REST_Response
{
    public $data;
    public function __construct($data)
    {
        $this->data = $data;
    }
    public function is_error()
    {
        return false;
    }
    public function get_data()
    {
        return $this->data;
    }
}

function rest_do_request($request)
{
    if (strpos($request->route, '/categories') !== false) {
        return new WP_REST_Response(array(
            'items' => array(
                array('slug' => 'test', 'name' => 'Mock Org Category')
            )
        ));
    }
    // Default fallback for orgs
    return new WP_REST_Response(
        array('item' => array('name' => 'Bound Org Name', 'ratingAverage' => 4.3))
    );
}

// Reflection helper to access private methods
function callMethod($obj, $name, array $args)
{
    $class = new ReflectionClass('Attention_Directory_API_Proxy');
    $method = $class->getMethod($name);
    return $method->invokeArgs($obj, $args);
}

define('ABSPATH', true);
require_once __DIR__ . '/src/api-proxy.php';

$success = true;

echo "Running WP Plugin Tests...\n";

// Test 1: fetch_from_api constructs URL and Authorization header correctly
update_option('attention_directory_base_url', 'https://api.test');
update_option('attention_directory_tenant_slug', 'demo-tenant');
update_option('attention_directory_api_key', 'test-secret');

$result = callMethod(null, 'fetch_from_api', ['/organizations', ['city' => 'LA']]);

// Verify request
if (count($wp_remote_responses) !== 1) {
    echo "FAIL: Expected 1 remote request, got " . count($wp_remote_responses) . "\n";
    $success = false;
}

$req = $wp_remote_responses[0];
if ($req['url'] !== 'https://api.test/v1/tenants/demo-tenant/directory/organizations?city=LA') {
    echo "FAIL: Incorrect URL generated: " . $req['url'] . "\n";
    $success = false;
}

if (!isset($req['args']['headers']['Authorization']) || $req['args']['headers']['Authorization'] !== 'Bearer test-secret') {
    echo "FAIL: Incorrect auth header\n";
    $success = false;
}

// Check transient set
$cache_key = 'attn_dir_' . md5('https://api.test/v1/tenants/demo-tenant/directory/organizations?city=LA');
if (!isset($wp_transients[$cache_key])) {
    echo "FAIL: Transient cache not set properly\n";
    $success = false;
}

if (!is_array($result) || $result['items'][0]['name'] !== 'Mock Org') {
    echo "FAIL: Result parsing failed\n";
    $success = false;
}

if ($success) {
    echo "API PROXY MOCK TESTS PASSED.\n";
} else {
    exit(1);
}

// Ensure the files are loaded
require_once __DIR__ . '/src/shortcodes.php';
require_once __DIR__ . '/src/block-bindings.php';

// Test 2: Shortcodes output safely
$sc_output = attention_directory_categories_shortcode(array());
if (strpos($sc_output, 'Mock Org') === false && strpos($sc_output, '<ul') === false) {
    echo "FAIL: Shortcode rendering failed or didn't fetch from proxy.\n";
    $success = false;
}

// Test 3: Block bindings fetch and map correctly
$block_instance = new stdClass();
$block_instance->context = array('attentionOrganizationSlug' => 'test-org');

$bb_name = Attention_Directory_Block_Bindings::get_organization_binding_value(
    array('field' => 'name'),
    $block_instance,
    'content'
);

$bb_rating = Attention_Directory_Block_Bindings::get_organization_binding_value(
    array('field' => 'rating_average'),
    $block_instance,
    'content'
);

if ($bb_name !== 'Bound Org Name') {
    echo "FAIL: Block Binding mapping for name failed ($bb_name).\n";
    $success = false;
}
if ($bb_rating !== '4.3') {
    echo "FAIL: Block Binding mapping for rating failed ($bb_rating).\n";
    $success = false;
}

if ($success) {
    echo "ALL WP PLUGIN MOCK TESTS PASSED.\n";
    exit(0);
} else {
}
