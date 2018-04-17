# wds-breadcrumbs
Simple breadcrumbs used on WebDevStudios.com

<a href="https://webdevstudios.com/contact/"><img src="https://webdevstudios.com/wp-content/uploads/2018/04/wds-github-banner.png" alt="WebDevStudios. WordPress for big brands."></a>

## How to Use
```
function wds_do_breadcrumbs() {

	// Check for WDS Breadcrumbs
	if ( ! class_exists( 'WDS_Breadcrumbs' ) ) {
		return false;
	}

	$breadcrumbs = new WDS_Breadcrumbs();
	return $breadcrumbs->do_breadcrumbs();
}
```
