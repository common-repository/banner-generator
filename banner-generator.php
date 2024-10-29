<?php
/**
 * @package Banner generator
 * @author Jure Ham
 * @version 0.6.4
 */
/*
Plugin Name: Banner Generator
Plugin URI: http://blog.hamsworld.net/2009/06/06/wordpress-plugin-banner-generator-2/
Description: Generates image with the last's posts title. Edit bGen settings under Settings menu. Your upload folder has to be writable.
Author: Jure Ham
Version: 0.6.4
Author URI: http://blog.hamsworld.net/
*/
function get_image_path($absolute) {
	$path_to_image = get_option('upload_path');
	if ($path_to_image) {
		$path_to_image .= "/banner.png";
	} else {
		$path_to_image .= "wp-content/uploads/banner.png";
	}
	if ($absolute && $path_to_image[0] != '/' && $path_to_image[1] != ':') {
		$path_to_image = ABSPATH.$path_to_image;
	}
	return $path_to_image;
}

function generate() {
	/* ------------------------ settings  ------------------------ */

	check_settings(false);

	$path_to_image = get_image_path(true);
	$plugin_path = dirname(__FILE__);

	//Background image(png)
	$bg = get_option('bgen_bg');

	//Header text, font, color and size
	$hdr_size = get_option('bgen_hdr_size');
	$hdr_fc = sscanf(get_option('bgen_hdr_fc'), '%2x%2x%2x');
	$head = get_option('bgen_hdr_text');
	$hdr_px = get_option('bgen_hdr_px');
	$hdr_py = get_option('bgen_hdr_py');

	//Title font, color and size

	$logo = get_option('bgen_logo');

	$bd_fc = sscanf(get_option('bgen_bd_fc'), '%2x%2x%2x');
	$bd_size = get_option('bgen_bd_size');
	$bd_px = get_option('bgen_bd_px');
	$bd_py = get_option('bgen_bd_py');
	$maxlen = get_option('bgen_maxl');


	//Background
	$bg_w = get_option('bgen_bg_w');
	$bg_h = get_option('bgen_bg_h');
	$bg_co = sscanf(get_option('bgen_bg_co'), '%2x%2x%2x');
	$bg_tr = get_option('bgen_bg_tr');
	$bg_tc = sscanf(get_option('bgen_bg_tc'), '%2x%2x%2x');
	$bg_size = get_option('bgen_bg_size');
	$bg_px = get_option('bgen_bg_px');
	$bg_py = get_option('bgen_bg_py');
	$bg_bw = get_option('bgen_bg_bw');
	$bg_bc = sscanf(get_option('bgen_bg_bc'), '%2x%2x%2x');
	$bg_title = get_option('bgen_bg_title');

	$blog_title = get_option('bgen_blog_title');

	//Fonts
	$bg_font = $plugin_path.'/fonts/'.get_option('bgen_bg_font');
	$bd_font = $plugin_path.'/fonts/'.get_option('bgen_bd_font');
	$hdr_font = $plugin_path.'/fonts/'.get_option('bgen_hdr_font');

	/* ------------------------ End of settings ------------------------ */

	$body = get_last_post_title();

	/* Create bg image */
	$im = @imagecreatefrompng($bg);

	if (!$im) {
	  $im = imagecreatetruecolor($bg_w, $bg_h);
	  imagealphablending($im, true);
	  imagesavealpha($im, true);
	  $trans = imagecolorallocatealpha($im, 0, 0, 0, 127);
	  $color = imagecolorallocate($im, $bg_co[0], $bg_co[1], $bg_co[2]);
	  $border = imagecolorallocate($im, $bg_bc[0], $bg_bc[1], $bg_bc[2]);
	  imagefill($im, 0, 0, $trans);

	  --$bg_w;
	  --$bg_h;

	  imagefillroundedrect($im, 0, 0, $bg_w, $bg_h, $bg_tr, $border);
	  imagefillroundedrect($im, $bg_bw, $bg_bw, $bg_w - ($bg_bw), $bg_h - ($bg_bw), $bg_tr, $color);

	}

	if ($bg_title == 'checked') {

		$im_logo = @imagecreatefrompng($logo);

		if ($im_logo) {

			imagealphablending($im_logo, true);
			imagesavealpha($im_logo, true);

			$logo_size = getimagesize($logo);

			imagecopy($im, $im_logo, $bg_px, $bg_py, 0, 0, $logo_size[0], $logo_size[1]);

			imagedestroy($im_logo);
		}	else {
			$colort = imagecolorallocate($im, $bg_tc[0], $bg_tc[1], $bg_tc[2]);
			imagefttext($im, $bg_size, 0, $bg_px, $bg_py, $colort, $bg_font, $blog_title);
		}
	}

	if (strlen($body) > $maxlen) {
		$body = substr($body, 0, $maxlen - 3)."...";
	}

	$colorn = imagecolorallocate($im, $hdr_fc[0], $hdr_fc[1], $hdr_fc[2]);
	$colorb = imagecolorallocate($im, $bd_fc[0], $bd_fc[1], $bd_fc[2]);

	imagealphablending($im, true);
	imagesavealpha($im, true);

	imagefttext($im, $hdr_size, 0, $hdr_px, $hdr_py, $colorn, $hdr_font, $head);
	imagefttext($im, $bd_size, 0, $bd_px, $bd_py, $colorb, $bd_font, $body);

	imagepng($im, $path_to_image);
	imagedestroy($im);

}

add_action('publish_post', 'generate');
add_action('update_post', 'generate');
add_action('deleted_post', 'generate');
register_activation_hook( __FILE__, 'generate' );

function get_last_post_title() {


	global $wpdb;

	$post_title = $wpdb->get_results("SELECT post_title FROM $wpdb->posts WHERE post_date =
		(SELECT max(post_date) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type='post')");

//	$post_title = $wpdb->get_results("SELECT post_title FROM $wpdb->where id = (select id from $pwdb->posts having max(post_date) group by post_date)");

	$title = $post_title[0]->post_title;

	//if db fails
	if (!$title) {

		$dom = new DomDocument;
		$dom->load(get_option('siteurl').'/?feed=rss');

		$title = $dom -> getElementsByTagName("title");
		$title = $title -> item(1);
		$title = $title -> nodeValue;

	}

	return $title;

}

function imagefillroundedrect($im,$x,$y,$cx,$cy,$rad,$col) {

// Draw the middle cross shape of the rectangle

	imagefilledrectangle($im,$x,$y+$rad,$cx,$cy-$rad,$col);
	imagefilledrectangle($im,$x+$rad,$y,$cx-$rad,$cy,$col);

	$dia = $rad*2;

// Now fill in the rounded corners

	imagefilledellipse($im, $x+$rad, $y+$rad, $dia, $dia, $col);
	imagefilledellipse($im, $x+$rad, $cy-$rad, $dia, $dia, $col);
	imagefilledellipse($im, $cx-$rad, $cy-$rad, $dia, $dia, $col);
	imagefilledellipse($im, $cx-$rad, $y+$rad, $dia, $dia, $col);
}

function font_name($strName) {
	$ext = strrchr($strName, '.');

	$exten = substr($strName, -strlen($ext) + 1, strlen($strName));
	if ($exten != "ttf")
		return false;

	if($ext !== false) {
		$strName = substr($strName, 0, -strlen($ext));
	}

	return preg_replace("/([A-Z])/", " $1", $strName);
}


/* -------------- admin --------------- */

add_action('admin_menu','bgen_admin');

function check_settings($all) {
	$options = array(
		'bgen_hdr_size' => 8,
		'bgen_hdr_text' => 'Last post:',
		'bgen_hdr_fc' => 'AAAAAA',
		'bgen_bd_size' => 11,
		'bgen_bd_fc' => 'FFFFFF',
		'bgen_hdr_px' => 15,
		'bgen_hdr_py' => 44,
		'bgen_bd_px' => 20,
		'bgen_bd_py' => 58,
		'bgen_maxl' => 58,
		'bgen_logo' => dirname(__FILE__)."/logo.png",
		'bgen_bg' => dirname(__FILE__)."/bg.png",
		'bgen_bg_w' => 400,
		'bgen_bg_h' => 65,
		'bgen_bg_co' => '444444',
		'bgen_bg_tr' => 10,
		'bgen_bg_size' => 25,
		'bgen_bg_px' => 10,
		'bgen_bg_py' => 32,
		'bgen_bg_bw' => 1,
		'bgen_bg_bc' => 'CCCCCC',
		'bgen_bg_title' => 'checked',
		'bgen_blog_title' => get_option('blogname'),
		'bgen_bg_font' => 'okolaksBoldItalic.ttf',
		'bgen_bd_font' => 'Sans.ttf',
		'bgen_hdr_font' => 'Sans.ttf',
		'bgen_bg_tc' => 'ffa438'
	);

	foreach ($options as $key => $value) {
		if (get_option($key) == "" || $all == true)
			update_option($key, $value);
	}
}

function bgen_admin()
{

	if ( function_exists('add_submenu_page') ) {
		add_options_page('bGen', 'bGen', 9, basename(__FILE__), 'bgen_manage');
	}

}

//PHP < 5.1.0 fix
if (!function_exists('array_intersect_key')) {
	function array_intersect_key()
	{
		$arrs = func_get_args();
		$result = array_shift($arrs);
		foreach ($arrs as $array) {
			foreach ($result as $key => $v) {
				if (!array_key_exists($key, $array)) {
					unset($result[$key]);
				}
			}
		}
		return $result;
   }
}

function bgen_manage() {


	if (isset($_POST['bgen_hdr_size'])) {

		$options = array('bgen_hdr_size' => 0, 'bgen_hdr_text' => 0, 'bgen_hdr_fc' => 0, 'bgen_bd_size' => 0, 'bgen_bd_fc' => 0, 'bgen_hdr_px' => 0, 'bgen_hdr_py' => 0, 'bgen_bd_px' => 0, 'bgen_bd_py' => 0, 'bgen_maxl' => 0, 'bgen_logo' => 0, 'bgen_bg' => 0, 'bgen_bg_w' => 0, 'bgen_bg_h' => 0, 'bgen_bg_co' => 0, 'bgen_bg_tr' => 0, 'bgen_bg_size' => 0, 'bgen_bg_px' => 0, 'bgen_bg_py' => 0, 'bgen_bg_bw' => 0, 'bgen_bg_bc' => 0, 'bgen_bg_title' => 0, 'bgen_blog_title' => 0, 'bgen_bg_font' => 0, 'bgen_bd_font' => 0, 'bgen_hdr_font' => 0, 'bgen_bg_tc' => 0);

		foreach (array_intersect_key($_POST, $options) as $key => $value) {
			update_option($key, $value);
		}

		if (isset($_POST['bgen_bg_title'])) {
			update_option('bgen_bg_title', 'checked');
		} else {
			update_option('bgen_bg_title', 'unchecked');
		}

		echo '<div class="updated"><p><strong>Options saved</strong></p></div>';

		generate();
	}

	if (isset($_POST['reset'])) {
		check_settings(true);
		generate();
	} else {
		check_settings(false);
	}

	$hdr_text = get_option('bgen_hdr_text');
	$hdr_size = get_option('bgen_hdr_size');
	$bd_size = get_option('bgen_bd_size');

	$hdr_fc = get_option('bgen_hdr_fc');
	$bd_fc = get_option('bgen_bd_fc');

	$hdr_px = get_option('bgen_hdr_px');
	$hdr_py = get_option('bgen_hdr_py');

	$bd_px = get_option('bgen_bd_px');
	$bd_py = get_option('bgen_bd_py');

	$maxl = get_option('bgen_maxl');
	$logo = get_option('bgen_logo');

	$bg = get_option('bgen_bg');
	$bg_w = get_option('bgen_bg_w');
	$bg_h = get_option('bgen_bg_h');
	$bg_co = get_option('bgen_bg_co');
	$bg_tr = get_option('bgen_bg_tr');
	$bg_tc = get_option('bgen_bg_tc');
	$bg_size = get_option('bgen_bg_size');
	$bg_px = get_option('bgen_bg_px');
	$bg_py = get_option('bgen_bg_py');

	$bg_bw = get_option('bgen_bg_bw');
	$bg_bc = get_option('bgen_bg_bc');

	$bg_title = get_option('bgen_bg_title');

	$blog_title = get_option('bgen_blog_title');

	$bg_font = get_option('bgen_bg_font');
	$bd_font = get_option('bgen_bd_font');
	$hdr_font = get_option('bgen_hdr_font');

	$fonts_folder = dirname(__FILE__).'/fonts';
	$fonts_read = opendir($fonts_folder);
	while (($font = readdir($fonts_read)) !== false) {
		if ($font != "." && $font != "..")
			$fonts[] = $font;
	}
	sort($fonts);

?>
<div class="wrap">
<h2>Banner Generator Options
</h2>

<form name="form1" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">

	<h3>Last post title settings:</h3>

	Font<br/>
	<select name="bgen_bd_font">
		<?php
		foreach ($fonts as $value) {
			$selected = ($value == $bd_font) ? 'selected' : '';
			if (font_name($value))
				echo '<option value="'.$value.'" '.$selected.'>'.font_name($value).'</option>';
		}
		?>
	</select><br/>

	Font size<br/>
	<input type="text" name="bgen_bd_size" value="<?php echo $bd_size;?>"><br/>

	Font color (FFFFFF for white)<br/>
	<input type="text" name="bgen_bd_fc" value="<?php echo $bd_fc;?>"><br/>

	Text margin left<br/>
	<input type="text" name="bgen_bd_px" value="<?php echo $bd_px;?>"><br/>

	Text margin top<br/>
	<input type="text" name="bgen_bd_py" value="<?php echo $bd_py;?>"><br/>

	Max text length<br/>
	<input type="text" name="bgen_maxl" value="<?php echo $maxl;?>"><br/>
	<br/>
	<hr/>

	<h3>Blog logo settings:</h3>
	<input type="checkbox" name="bgen_bg_title" <?php echo $bg_title;?> > Show blog logo<br/><br/>

	Try to use this logo (url or absoluth path to PNG image).<br/>
	If image isn't available, blog title will be shown.<br/>
	<input style="width:450px;" type="text" name="bgen_logo" value="<?php echo $logo;?>"><br/><br/>

	Logo margin left<br/>
	<input type="text" name="bgen_bg_px" value="<?php echo $bg_px;?>"><br/>

	Logo margin top<br/>
	<input type="text" name="bgen_bg_py" value="<?php echo $bg_py;?>"><br/><br/>

	Blog Title<br/>
	<input type="text" name="bgen_blog_title" value="<?php echo $blog_title;?>"><br/>

	Font<br/>
	<select name="bgen_bg_font">
		<?php
		foreach ($fonts as $value) {
			$selected = ($value == $bg_font) ? 'selected' : '';
			if (font_name($value))
				echo '<option value="'.$value.'" '.$selected.'>'.font_name($value).'</option>';
		}
		?>
	</select><br/>

	Font size<br/>
	<input type="text" name="bgen_bg_size" value="<?php echo $bg_size;?>"><br/>

	Font color (FFFFFF for white)<br/>
	<input type="text" name="bgen_bg_tc" value="<?php echo $bg_tc;?>"><br/>

	<br/>
	<hr/>

	<h3>Header settings:</h3>

	Message<br/>
	<input type="text" name="bgen_hdr_text" value="<?php echo $hdr_text;?>"><br/>

	Font<br/>
	<select name="bgen_hdr_font">
		<?php
		foreach ($fonts as $value) {
			$selected = ($value == $hdr_font) ? 'selected' : '';
			if (font_name($value))
				echo '<option value="'.$value.'" '.$selected.'>'.font_name($value).'</option>';
		}
		?>
	</select><br/>

	Font size<br/>
	<input type="text" name="bgen_hdr_size" value="<?php echo $hdr_size;?>"><br/>

	Font color (FFFFFF for white)<br/>
	<input type="text" name="bgen_hdr_fc" value="<?php echo $hdr_fc;?>"><br/>

	Text margin left<br/>
	<input type="text" name="bgen_hdr_px" value="<?php echo $hdr_px;?>"><br/>

	Text margin top<br/>
	<input type="text" name="bgen_hdr_py" value="<?php echo $hdr_py;?>"><br/>
	<br />
	<hr/>

	<h3>Background settings</h3>

	Try to use this background (url or absoluth path to PNG image).<br/>
	If image isn't available, background will be generated.<br/>
	<input style="width:450px;" type="text" name="bgen_bg" value="<?php echo $bg;?>"><br/><br/>

	Dimensions<br/>
	Width: <input style="width:60px;" type="text" name="bgen_bg_w" value="<?php echo $bg_w;?>"><br/>
	Height: <input style="width:60px;" type="text" name="bgen_bg_h" value="<?php echo $bg_h;?>"><br/>

	Color (FFFFFF for white)<br/>
	<input type="text" name="bgen_bg_co" value="<?php echo $bg_co;?>"><br/>

	Corners radius (in px)<br/>
	<input type="text" name="bgen_bg_tr" value="<?php echo $bg_tr;?>"><br/>

	Border width (in px)<br/>
	<input type="text" name="bgen_bg_bw" value="<?php echo $bg_bw;?>"><br/>

	Border color (FFFFFF for white)<br/>
	<input type="text" name="bgen_bg_bc" value="<?php echo $bg_bc;?>"><br/>

	<br/>
	<hr/>

	<?php $img_url = get_option('siteurl') . '/' . get_image_path(false); ?>
	<img src="<?=$img_url ?>" /><br /><br />
	Image url:<br/>
	<div style="background:#fff; border: 1px solid #ddd; padding:3px"><a href ="<?php echo $img_url;?>"><?php echo $img_url;?></a></div>

	HTML code:<br/>
	<div style="background:#fff; border: 1px solid #ddd; padding:3px">&lt;a href ="<?php echo get_option('siteurl');?>"&gt;&lt;img src="<?php echo $img_url;?>"&gt;&lt;/a&gt;</div>

	BB code:<br/>
	<div style="background:#fff; border: 1px solid #ddd; padding:3px">[url=<?php echo get_option('siteurl');?>][img]<?php echo $img_url;?>[/img][/url]</div>
	<hr/>
	<p class="submit">
	<input type="submit" name="Submit" value="Update Options &raquo;" />
	</p>
</form>

<form name="form2" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
	<input type="hidden" name="reset" value="true">
	<p class="submit">
	<input type="submit" name="Submit" value="Reset to default &raquo;" />
	</p>
</form>
</div>
<?php
}

?>
