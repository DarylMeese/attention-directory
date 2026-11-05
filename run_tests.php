<?php
/** Fast local contract tests; real WordPress acceptance is recorded separately. */
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
class WP_Error { public $code, $message, $data; function __construct($c='', $m='', $d=array()) { $this->code=$c; $this->message=$m; $this->data=$d; } }
class WP_REST_Server { const READABLE='GET'; }
class WP_REST_Request {
    public $route, $query=array(); function __construct($m,$r) { $this->route=$r; }
    function set_query_params($q) { $this->query=$q; } function get_query_params() { return $this->query; }
    function get_param($k) { return $k==='slug' ? basename($this->route) : ($this->query[$k] ?? null); }
}
class WP_REST_Response {
    public $data, $status, $headers=array(); function __construct($d,$s=200) { $this->data=$d; $this->status=$s; }
    function header($k,$v) { $this->headers[$k]=$v; } function get_data() { return $this->data; } function is_error() { return $this->status>=400; }
}
$options=array(); $requests=array(); $settings=array(); $scripts=array(); $styles=array(); $shortcodes=array(); $tests=0; $can_manage=true; $nonce=true;
class TestWPDB { public $options='wp_options', $queries=array(); function esc_like($s) { return addcslashes($s,'_%\\'); } function prepare($q,$v) { return $q.' '. $v; } function query($q) { $this->queries[]=$q; } }
$wpdb=new TestWPDB();
$canonical=array('organizationId'=>'org-public','slug'=>'test-org','displayName'=>'Real public name','shortDescription'=>'Public summary','longDescription'=>'<p>Public details</p>','logoMediaUrl'=>'https://images.example/logo.png','ratingAverage'=>4.3,'ratingCount'=>12,'primaryPhone'=>'555-0100','isOpen'=>null);
$response=array('response'=>array('code'=>200),'body'=>json_encode(array('items'=>array($canonical),'paging'=>array('page'=>1,'total_pages'=>2,'has_next'=>true,'has_prev'=>false),'facets'=>array())));
function get_option($k,$d=false) { global $options; return $options[$k]??$d; }
function update_option($k,$v) { global $options; $options[$k]=$v; }
function wp_safe_remote_get($u,$a) { global $requests,$response; $requests[]=array('url'=>$u,'args'=>$a); return $response; }
function wp_remote_retrieve_response_code($r) { return $r['response']['code']??500; }
function wp_remote_retrieve_body($r) { return $r['body']??''; }
function wp_parse_url($u) { return parse_url($u); }
function is_wp_error($v) { return $v instanceof WP_Error; }
function rest_ensure_response($d) { return new WP_REST_Response($d); }
function rest_do_request($r) {
    if (strpos($r->route,'/organizations/')!==false) $result=Attention_Directory_API_Proxy::get_organization_single($r);
    elseif (strpos($r->route,'/categories')!==false) $result=Attention_Directory_API_Proxy::get_categories($r);
    else $result=Attention_Directory_API_Proxy::get_organizations($r);
    return is_wp_error($result)?new WP_REST_Response(array('message'=>$result->message),$result->data['status']):$result;
}
function add_query_arg($k,$v=null,$u='') { if(is_array($k)){ $q=$k; $u=$v??''; }else $q=array($k=>$v); return ($u?:'https://wordpress.example/directory/').'?'.http_build_query($q); }
function remove_query_arg($k) { return 'https://wordpress.example/directory/'; }
function sanitize_text_field($s) { return trim(strip_tags($s)); }
function sanitize_title($s) { return strtolower(preg_replace('/[^a-zA-Z0-9-]/','-',$s)); }
function wp_unslash($s) { return stripslashes($s); }
function esc_html($s) { return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
function esc_attr($s) { return esc_html($s); }
function esc_url($s) { return preg_match('/^(javascript|data):/i',(string)$s)?'':(string)$s; }
function esc_url_raw($s) { return esc_url($s); }
function __($s,$d='') { return $s; } function _e($s,$d='') { echo $s; }
function esc_html__($s,$d='') { return esc_html($s); } function esc_attr__($s,$d='') { return esc_attr($s); }
function esc_html_e($s,$d='') { echo esc_html($s); }
function wp_kses_post($s) { return $s; }
function shortcode_atts($d,$a,$n='') { return array_merge($d,(array)$a); }
function wp_unique_id($p='') { static $n=0; return $p.(++$n); }
function number_format_i18n($v,$n=0) { return number_format($v,$n); }
function add_action($a,$b,$c=10,$d=1) {} function add_filter($a,$b,$c=10,$d=1) {}
function add_shortcode($a,$b) { global $shortcodes; $shortcodes[$a]=$b; }
function register_rest_route($a,$b,$c) {} function register_block_bindings_source($a,$b) {}
function register_setting($g,$k,$c) { global $settings; $settings[$k]=$c; }
function add_options_page(...$a) {} function add_settings_error(...$a) {}
function current_user_can($c) { global $can_manage; return $can_manage; }
function check_admin_referer(...$a) { global $nonce; return $nonce; }
function settings_fields($g) { echo '<input name="_wpnonce" value="synthetic" />'; }
function do_settings_sections($g) {} function submit_button() { echo '<button>Save changes</button>'; }
function wp_nonce_field(...$a) { echo '<input name="attention_directory_purge_cache_nonce" value="synthetic" />'; }
function checked($v) { if($v) echo 'checked'; } function rest_sanitize_boolean($v) { return (bool)$v; }
function nocache_headers() {} function home_url($s='') { return 'https://wordpress.example'.$s; }
function plugin_dir_path($f) { return __DIR__.'/'; } function plugin_dir_url($f) { return 'https://wordpress.example/wp-content/plugins/attention-directory/'; }
function plugin_basename($f) { return basename($f); } function register_activation_hook($f,$c) {}
function wp_enqueue_style(...$a) { global $styles; $styles[]=$a; }
function wp_enqueue_script(...$a) { global $scripts; $scripts[]=$a; }
function wp_add_inline_script(...$a) { global $scripts; $scripts[]=$a; }
define('ABSPATH',__DIR__.'/'); require __DIR__.'/attention-directory.php'; attention_directory_init();
function check($condition,$label) { global $tests; $tests++; if(!$condition) throw new Exception('FAIL: '.$label); }
function request_for($route,$query=array()) { $r=new WP_REST_Request('GET',$route);$r->set_query_params($query);return $r; }
function upstream($data,$status=200) { global $response;$response=array('response'=>array('code'=>$status),'body'=>is_string($data)?$data:json_encode($data)); }
update_option('attention_directory_base_url','https://app.attentionnetwork.net/api');update_option('attention_directory_tenant_slug','test-site');update_option('attention_directory_api_key','PRIVATE_LEGACY_SECRET');
$result=Attention_Directory_API_Proxy::get_organizations(request_for('/organizations',array('search'=>'coffee','page_size'=>999,'city'=>'Austin','tenantId'=>'FOREIGN')));
check($result->data['items'][0]['name']==='Real public name','canonical card name mapping');check($result->data['items'][0]['id']==='org-public','canonical organization identifier');
check($result->headers['Cache-Control']==='no-store','REST public responses no-store');
$sent=end($requests);check(strpos($sent['url'],'/api/v1/tenants/test-site/directory/organizations?')!==false,'canonical API path');check(strpos($sent['url'],'q=coffee')!==false,'search alias becomes canonical q');check(strpos($sent['url'],'page_size=100')!==false,'page size bounded100');check(strpos($sent['url'],'FOREIGN')===false,'caller cannot change configured site');check(!isset($sent['args']['headers']['Authorization']),'legacy administrator credential never forwarded');check($sent['args']['redirection']===0,'redirects cannot forward requests elsewhere');
$before=count($requests);Attention_Directory_API_Proxy::get_organizations(request_for('/organizations'));check(count($requests)===$before+1,'fresh upstream request instead of stale transient');
foreach(array(array('page'=>0),array('lat'=>'bad','lng'=>1),array('lat'=>91,'lng'=>0),array('lat'=>0),array('radius_mi'=>1),array('q'=>array('bad')),array('sort'=>'internal')) as $q) { check(is_wp_error(Attention_Directory_API_Proxy::get_organizations(request_for('/organizations',$q))),'invalid filter rejected'); }
check(!is_wp_error(Attention_Directory_API_Proxy::get_organizations(request_for('/organizations',array('lat'=>0,'lng'=>0,'radius_mi'=>1)))),'zero coordinates accepted');
upstream(array('profile'=>$canonical+array('privateAdminToken'=>'DO_NOT_EXPOSE')));
$single=Attention_Directory_API_Proxy::get_organization_single(request_for('/organizations/test-org'));
check($single->data['item']['name']==='Real public name','actual profile envelope unwrapped');check($single->data['item']['fullDescription']==='<p>Public details</p>','public profile description mapped');check(!isset($single->data['item']['privateAdminToken']),'unknown private top-level field omitted');
check(Attention_Directory_Block_Bindings::get_organization_binding_value(array('field'=>'name'),(object)array('context'=>array('attentionOrganizationSlug'=>'test-org')),'content')==='Real public name','binding uses actual proxy response');
check(Attention_Directory_Block_Bindings::get_organization_binding_value(array('field'=>'rating_average'),(object)array('context'=>array('attentionOrganizationSlug'=>'test-org')),'content')==='4.3','rating binding');
check(Attention_Directory_Block_Bindings::get_organization_binding_value(array('field'=>'locations'),(object)array('context'=>array('attentionOrganizationSlug'=>'test-org')),'content')===null,'unsupported array binding returns null');
check(strpos(attention_directory_org_shortcode(array('slug'=>'test-org')),'Real public name')!==false,'profile shortcode actual contract');
foreach (array(array(), array('primaryPhone'=>' ', 'primaryEmail'=>'', 'websiteUrl'=>null)) as $empty_contacts) {
    $profile=$canonical; unset($profile['primaryPhone'], $profile['primaryEmail'], $profile['websiteUrl']);
    upstream(array('profile'=>array_merge($profile,$empty_contacts)));
    $html=attention_directory_org_shortcode(array('slug'=>'test-org'));
    check(strpos($html,'Real public name')!==false && strpos($html,'attention-profile-contact')===false,'empty contact panel omitted while profile remains readable');
}
foreach (array('primaryPhone'=>'555-0100', 'primaryEmail'=>'qa@example.invalid', 'websiteUrl'=>'https://example.invalid') as $field=>$value) {
    $profile=$canonical; unset($profile['primaryPhone'], $profile['primaryEmail'], $profile['websiteUrl']);
    $profile[$field]=$value; upstream(array('profile'=>$profile));
    $html=attention_directory_org_shortcode(array('slug'=>'test-org'));
    check(strpos($html,'attention-profile-contact')!==false && strpos($html,$value)!==false,'a single real contact preserves the contact panel');
}
upstream(array('profile'=>$canonical));
$_GET=array('org_slug'=>'test-org');check(strpos(attention_directory_listing_shortcode(array()),'Back to directory')!==false,'listing supports same-page profile navigation');$_GET=array();
upstream(array('categories'=>array(array('slug'=>'coffee','name'=>'Coffee','count'=>2))));$cats=Attention_Directory_API_Proxy::get_categories(request_for('/categories'));check($cats->data['items'][0]['name']==='Coffee','actual categories envelope');check(strpos(end($requests)['url'],'/directory/categories')!==false,'canonical categories route');check(strpos(attention_directory_categories_shortcode(array()),'category=coffee')!==false,'category links filter actual directory');
upstream(array('collection'=>array('collectionId'=>'public','title'=>'Coffee','criteria'=>array('PRIVATE'),'items'=>array($canonical))));$collection=Attention_Directory_API_Proxy::get_collection(request_for('/collections/coffee'));check($collection->data['items'][0]['name']==='Real public name','collection items normalized');check(!isset($collection->data['collection']['criteria']),'collection internal criteria not exposed');
upstream(array('items'=>array($canonical),'paging'=>array('page'=>1,'total_pages'=>2,'has_next'=>true),'facets'=>array()));$_GET=array('q'=>'coffee','attn_page'=>'1');$html=attention_directory_listing_shortcode(array());check(strpos(end($requests)['url'],'q=coffee')!==false,'search form filters rendered listing');check(strpos($html,'attn_page=2')!==false,'real paging next link');$_GET=array();
$html=attention_directory_search_shortcode(array());check(strpos($html,'type="search"')!==false && strpos($html,'<label')!==false,'accessible labelled search');check(isset($shortcodes['attention_directory_search']),'documented search alias registered');
$_GET=array('page_id'=>'42','category'=>'coffee','attn_page'=>'9','org_slug'=>'old-profile');$html=attention_directory_search_shortcode(array());check(strpos($html,'name="page_id" value="42"')!==false,'search preserves plain WordPress page routing');check(strpos($html,'name="category" value="coffee"')!==false,'search preserves active category');check(strpos($html,'name="attn_page" value="1"')!==false&&strpos($html,'name="org_slug"')===false,'new search resets directory page and profile');$_GET=array();
upstream('PRIVATE RAW ERROR',500);$error=Attention_Directory_API_Proxy::get_organizations(request_for('/organizations'));check(is_wp_error($error)&&strpos($error->message,'PRIVATE')===false,'raw upstream errors never displayed');check(strpos(attention_directory_listing_shortcode(array()),'Failed to load directory')!==false,'human-readable shortcode error');
upstream('malformed');check(is_wp_error(Attention_Directory_API_Proxy::get_organizations(request_for('/organizations'))),'malformed response fails closed');
foreach(array('http://127.0.0.1','https://user:pass@example.com','https://example.com?token=secret') as $url){update_option('attention_directory_base_url',$url);check(is_wp_error(Attention_Directory_API_Proxy::get_organizations(request_for('/organizations'))),'unsafe API URL rejected');}update_option('attention_directory_base_url','https://app.attentionnetwork.net/api');
$flat=Attention_Directory_API_Proxy::flatten_organization_fields(array('rating'=>4.9,'reviewCount'=>0,'distance'=>0,'isOpen'=>'unknown'));check($flat['ratingAverage']===null,'no invented average without reviews');check($flat['distance_mi']===0.0,'zero distance preserved');check($flat['is_open']===null,'unknown hours remain unknown');
check(attention_format_distance('unknown')==='','bad distance not coerced to zero');check(attention_render_status('unknown')==='','unknown status not shown closed');check(attention_render_stars(9)==='','invalid review rating omitted');
attention_directory_register_settings();check(!isset($settings['attention_directory_api_key']),'legacy credential cannot be edited accidentally');check(isset($settings['attention_directory_push_enabled']),'explicit optional push flag');
check(attention_directory_validate_push_id('../../script')==='','invalid push application path rejected');
ob_start();attention_directory_render_settings_page();$admin=ob_get_clean();check(strpos($admin,'PRIVATE_LEGACY_SECRET')===false,'settings HTML contains no stored secret');check(strpos($admin,'_wpnonce')!==false,'WordPress settings nonce retained');
$can_manage=false;ob_start();attention_directory_render_settings_page();check(ob_get_clean()==='','settings require manage_options');$can_manage=true;
$_SERVER['REQUEST_METHOD']='POST';$_POST=array('attention_directory_purge_cache'=>1);$nonce=false;ob_start();attention_directory_render_settings_page();ob_end_clean();check(count($wpdb->queries)===0,'invalid purge nonce causes no cache mutation');
$nonce=true;ob_start();attention_directory_render_settings_page();ob_end_clean();check(count($wpdb->queries)===2,'valid nonce permits only legacy cache cleanup');check(strpos($wpdb->queries[0],'\_transient\_attn\_dir\_')!==false,'cache cleanup escapes SQL wildcard prefix');
$can_manage=false;ob_start();attention_directory_render_settings_page();ob_end_clean();check(count($wpdb->queries)===2,'nonadministrator cannot purge cache even with nonce');$can_manage=true;$_POST=array();unset($_SERVER['REQUEST_METHOD']);
check(attention_directory_validate_slug('../other-site')==='test-site','invalid site slug preserves saved site');check(attention_directory_validate_base_url('javascript:bad')==='https://app.attentionnetwork.net/api','invalid API URL preserves saved configuration');
$scripts=array();attention_directory_enqueue_assets();check(count($scripts)===0,'push defaults disabled');update_option('attention_directory_push_app_id','valid-test-app-id');update_option('attention_directory_push_enabled',true);$scripts=array();attention_directory_enqueue_assets();check(count($scripts)===3,'push SDK/init/handler only with explicit configuration');check(strpos($scripts[1][1],'autoRegister:false')!==false,'no automatic notification permission prompt');
echo 'PASS: '.$tests." contract and rendering assertions.\n";
