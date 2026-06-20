<?php
/**
 * One-time MEDIA / UPLOAD diagnostic for Hostinger.
 *
 * Tells you EXACTLY why image uploads fail and why the Media Library is empty:
 * disk space, uploads-folder permissions, PHP limits, or image library missing.
 *
 * HOW TO USE (Hostinger File Manager):
 * 1. Upload this file to public_html (same folder as wp-config.php)
 * 2. Log in to WordPress admin in another tab (as administrator)
 * 3. Open: https://rudrajewellery.co.in/4-VISIT-ONCE-DIAGNOSE-MEDIA.php
 * 4. Read the report. It auto-fixes folder permissions where it can.
 * 5. DELETE this file from public_html immediately after.
 *
 * @package JwelleryJewelry
 */

$wp_load = __DIR__ . '/wp-load.php';
if ( ! file_exists( $wp_load ) ) {
	header( 'Content-Type: text/plain; charset=utf-8' );
	die( "wp-load.php not found. Upload this file to public_html (WordPress root).\n" );
}

require $wp_load;

header( 'Content-Type: text/html; charset=utf-8' );

if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
	// Fallback for when the auth cookie does not match (e.g. www vs non-www domain):
	// allow access with a secret key in the URL. Temporary file — delete after use.
	$given_key = isset( $_GET['key'] ) ? (string) $_GET['key'] : '';
	if ( ! hash_equals( 'rudra-diag-2026', $given_key ) ) {
		echo '<p>Access denied. Either:</p><ol>'
			. '<li>Open this URL with <strong>www</strong>: '
			. '<code>https://www.rudrajewellery.co.in/4-VISIT-ONCE-DIAGNOSE-MEDIA.php</code>, or</li>'
			. '<li>Add the secret key to the URL: '
			. '<code>https://rudrajewellery.co.in/4-VISIT-ONCE-DIAGNOSE-MEDIA.php?key=rudra-diag-2026</code></li>'
			. '</ol>';
		exit;
	}
}

/**
 * Pretty bytes.
 *
 * @param float $bytes Bytes.
 * @return string
 */
function jwd_bytes( $bytes ) {
	$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
	$bytes = max( (float) $bytes, 0 );
	$pow   = $bytes ? floor( log( $bytes, 1024 ) ) : 0;
	$pow   = min( $pow, count( $units ) - 1 );
	return round( $bytes / pow( 1024, $pow ), 2 ) . ' ' . $units[ (int) $pow ];
}

$ok   = '<span style="color:#137333;font-weight:bold;">PASS</span>';
$bad  = '<span style="color:#c5221f;font-weight:bold;">FAIL</span>';
$warn = '<span style="color:#b06000;font-weight:bold;">WARN</span>';

echo '<!doctype html><meta charset="utf-8"><title>Media Diagnostic</title>';
echo '<body style="font-family:system-ui,Arial,sans-serif;max-width:820px;margin:30px auto;line-height:1.5;color:#202124;">';
echo '<h1>Media / Upload Diagnostic</h1>';
echo '<p style="background:#fff3cd;padding:10px 14px;border-radius:6px;">Delete this file from <code>public_html</code> as soon as you are done reading.</p>';

$findings = array();

/* ── 1. Uploads directory ─────────────────────────────────────────────── */
$upload = wp_upload_dir();
echo '<h2>1. Uploads folder</h2><ul>';
if ( ! empty( $upload['error'] ) ) {
	echo '<li>' . $bad . ' WordPress reports: <code>' . esc_html( $upload['error'] ) . '</code></li>';
	$findings[] = 'WordPress upload_dir() returned an error — this is the direct cause. See message above.';
} else {
	echo '<li>' . $ok . ' Base dir: <code>' . esc_html( $upload['basedir'] ) . '</code></li>';
	echo '<li>Current month dir: <code>' . esc_html( $upload['path'] ) . '</code></li>';
}

$basedir = isset( $upload['basedir'] ) ? $upload['basedir'] : ( WP_CONTENT_DIR . '/uploads' );

/* exists? */
if ( is_dir( $basedir ) ) {
	echo '<li>' . $ok . ' uploads/ exists</li>';
} else {
	echo '<li>' . $bad . ' uploads/ does NOT exist. Attempting to create…</li>';
	if ( wp_mkdir_p( $basedir ) ) {
		echo '<li>' . $ok . ' Created uploads/</li>';
	} else {
		echo '<li>' . $bad . ' Could not create uploads/ — permissions problem.</li>';
		$findings[] = 'The uploads folder is missing and could not be created. Set wp-content to 755 and owner to your hosting user in Hostinger File Manager.';
	}
}

/* writable? */
if ( is_writable( $basedir ) ) {
	echo '<li>' . $ok . ' uploads/ is writable (perms ' . substr( sprintf( '%o', fileperms( $basedir ) ), -4 ) . ')</li>';
} else {
	echo '<li>' . $bad . ' uploads/ is NOT writable (perms ' . substr( sprintf( '%o', fileperms( $basedir ) ), -4 ) . '). Attempting chmod 755…</li>';
	@chmod( $basedir, 0755 );
	if ( is_writable( $basedir ) ) {
		echo '<li>' . $ok . ' Fixed — now writable.</li>';
	} else {
		echo '<li>' . $bad . ' Still not writable.</li>';
		$findings[] = 'wp-content/uploads is not writable. In Hostinger File Manager set its permissions to 755 (folders) and 644 (files), recursively.';
	}
}

/* actual write test */
$test_file = trailingslashit( $basedir ) . 'jwd-write-test-' . wp_generate_password( 6, false ) . '.txt';
$written   = @file_put_contents( $test_file, 'ok' );
if ( false !== $written ) {
	echo '<li>' . $ok . ' Wrote a real test file successfully.</li>';
	@unlink( $test_file );
} else {
	echo '<li>' . $bad . ' Could NOT write a test file to uploads/.</li>';
	$findings[] = 'Writing a file into uploads/ failed even though it appears writable — usually means the disk is FULL. See disk space below.';
}
echo '</ul>';

/* ── 2. Disk space ────────────────────────────────────────────────────── */
echo '<h2>2. Disk space</h2><ul>';
$free  = @disk_free_space( $basedir );
$total = @disk_total_space( $basedir );
if ( $free !== false ) {
	$pct = ( $total > 0 ) ? round( ( $free / $total ) * 100, 1 ) : 0;
	$line = 'Free: ' . jwd_bytes( $free ) . ' of ' . jwd_bytes( $total ) . ' (' . $pct . '% free)';
	if ( $free < 50 * 1024 * 1024 ) {
		echo '<li>' . $bad . ' ' . esc_html( $line ) . '</li>';
		$findings[] = 'DISK IS FULL (under 50 MB free). This is the #1 cause of "An error occurred in the upload" plus an empty Media Library. Delete large/unused files in Hostinger, or upgrade your plan. Check hPanel → Files → Disk usage, and wp-content/uploads for huge old images.';
	} elseif ( $free < 250 * 1024 * 1024 ) {
		echo '<li>' . $warn . ' ' . esc_html( $line ) . ' — getting low.</li>';
	} else {
		echo '<li>' . $ok . ' ' . esc_html( $line ) . '</li>';
	}
} else {
	echo '<li>' . $warn . ' disk_free_space() is disabled on this host — check disk usage in hPanel manually.</li>';
}
echo '</ul>';

/* ── 3. PHP limits ────────────────────────────────────────────────────── */
echo '<h2>3. PHP limits</h2><ul>';
$mem  = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
$umax = wp_convert_hr_to_bytes( ini_get( 'upload_max_filesize' ) );
$pmax = wp_convert_hr_to_bytes( ini_get( 'post_max_size' ) );
$exec = (int) ini_get( 'max_execution_time' );

echo '<li>memory_limit: <code>' . esc_html( ini_get( 'memory_limit' ) ) . '</code> ' . ( $mem >= 256 * 1024 * 1024 ? $ok : $warn ) . '</li>';
echo '<li>upload_max_filesize: <code>' . esc_html( ini_get( 'upload_max_filesize' ) ) . '</code> ' . ( $umax >= 8 * 1024 * 1024 ? $ok : $warn ) . '</li>';
echo '<li>post_max_size: <code>' . esc_html( ini_get( 'post_max_size' ) ) . '</code> ' . ( $pmax >= $umax ? $ok : $bad ) . '</li>';
echo '<li>max_execution_time: <code>' . esc_html( (string) $exec ) . 's</code> ' . ( ( 0 === $exec || $exec >= 30 ) ? $ok : $warn ) . '</li>';

if ( $mem < 128 * 1024 * 1024 ) {
	$findings[] = 'PHP memory_limit is low (' . ini_get( 'memory_limit' ) . '). Generating image thumbnails can exhaust it and abort the upload. In hPanel → Advanced → PHP Configuration, set memory_limit to 256M.';
}
if ( $pmax < $umax ) {
	$findings[] = 'post_max_size (' . ini_get( 'post_max_size' ) . ') is smaller than upload_max_filesize (' . ini_get( 'upload_max_filesize' ) . '). Uploads above post_max_size silently fail. Set post_max_size to 64M and upload_max_filesize to 64M in hPanel PHP Configuration.';
}
if ( $umax < 4 * 1024 * 1024 ) {
	$findings[] = 'upload_max_filesize is small (' . ini_get( 'upload_max_filesize' ) . '). A 4–8 MB WhatsApp photo will be rejected. Raise it to 64M in hPanel PHP Configuration.';
}
echo '</ul>';

/* ── 4. Image library ─────────────────────────────────────────────────── */
echo '<h2>4. Image processing</h2><ul>';
$has_gd      = extension_loaded( 'gd' );
$has_imagick = extension_loaded( 'imagick' ) && class_exists( 'Imagick' );
echo '<li>GD: ' . ( $has_gd ? $ok : $bad ) . '</li>';
echo '<li>Imagick: ' . ( $has_imagick ? $ok : $warn ) . '</li>';
if ( ! $has_gd && ! $has_imagick ) {
	$findings[] = 'Neither GD nor Imagick is enabled, so WordPress cannot process uploaded images at all. Enable the GD extension in hPanel → Advanced → PHP Configuration → PHP Extensions.';
}
echo '</ul>';

/* ── 5. Existing attachments in DB ────────────────────────────────────── */
echo '<h2>5. Existing media in database</h2><ul>';
$count = (int) ( wp_count_posts( 'attachment' )->inherit ?? 0 );
echo '<li>Attachments recorded in DB: <code>' . $count . '</code></li>';
if ( $count > 0 ) {
	echo '<li>' . $ok . ' Existing media DOES exist in the database. If the Library shows nothing, the <code>query-attachments</code> admin-ajax call is being blocked — almost always a cache or a low memory_limit timing out the request.</li>';
	$findings[] = 'There ARE ' . $count . ' attachments in the database but the Library appears empty — this points to the admin-ajax request failing (memory/timeout) or a cache plugin caching admin-ajax. After fixing PHP limits above, also purge LiteSpeed cache (hPanel → Advanced → Cache Manager → Purge All).';
} else {
	echo '<li>' . $warn . ' No attachments in DB — the library is genuinely empty (nothing uploaded yet or all deleted).</li>';
}
echo '</ul>';

/* ── Summary ──────────────────────────────────────────────────────────── */
echo '<h2 style="margin-top:30px;">What to do</h2>';
if ( empty( $findings ) ) {
	echo '<p>' . $ok . ' No server-side blocker detected by this script. Try the upload again; if it still fails, purge LiteSpeed cache (hPanel → Cache Manager → Purge All) and retry in an Incognito window.</p>';
} else {
	echo '<ol>';
	foreach ( $findings as $f ) {
		echo '<li style="margin-bottom:10px;">' . esc_html( $f ) . '</li>';
	}
	echo '</ol>';
}

echo '<p style="margin-top:24px;background:#fce8e6;padding:10px 14px;border-radius:6px;"><strong>Now delete this file</strong> (<code>4-VISIT-ONCE-DIAGNOSE-MEDIA.php</code>) from <code>public_html</code>.</p>';
echo '</body>';
