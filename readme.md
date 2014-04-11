# Remote Image

Downloads a remote image and saves it locally if it hasn't been done already. Images already downloaded will not be downloaded a second time, and will be interned/reused.

## Usage

```
$image = new cftp_remote_image( "http://placekitten.com/g/200/300", 0, "cute kittens" );
$attachment_id = $image->getID();
if ( !is_wp_error( $attachment_id ) ) {
    // hooray, do things with newly downloaded attachment
}
```
