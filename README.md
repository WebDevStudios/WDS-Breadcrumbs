# wds-breadcrumbs
Breadcrumbs from WDS7 that everyone keeps reusing from project-to-project

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
