# How image styles are read

`media_assist` provides one service, `media_assist.image_style_reader`
(`Drupal\media_assist\ImageStyleReader`). The other two modules get their
shapes from it, so what it reads is what they show.

## What a style comes to

`read($style_id)` returns the crop type, width, height and ratio a style
produces, or NULL for a style that does not exist. Each of the four is NULL
when the style does not set it.

The crop type is taken from the last crop effect in the style:

| Effect | Module |
|---|---|
| `crop_crop` | crop, as used by Image Widget Crop |
| `focal_point_crop` | focal_point |
| `focal_point_crop_by_width` | focal_point |
| `focal_point_scale_and_crop` | focal_point |

The width and height are taken from the last effect that sets each of them:

| Effect | Sets |
|---|---|
| `image_resize` | width and height |
| `image_scale` | width, height, or both |
| `image_scale_and_crop` | width and height |
| `focal_point_crop` | width and height |
| `focal_point_crop_by_width` | width |
| `focal_point_scale_and_crop` | width and height |

A style that crops with `crop_crop` and then scales to a width has a crop
type and a width but no height. A style that only crops has a crop type and
no box.

## How a ratio is named

The ratio is a `W:H` string chosen in this order:

1. The aspect ratio of the style's crop type, when the crop type has one and
   the style's width and height are within two percent of it, or when the
   style does not set both. A style at 266 by 236 with an 8:7 crop type is
   8:7.
2. The nearest aspect ratio, within two percent, among every crop type on
   the site and then the list in `ImageStyleReader::NAMED_RATIOS` (21:9,
   2:1, 16:9, 3:2, 4:3, 5:4, 1:1, 4:5, 3:4, 2:3, 9:16). This is how styles
   under Focal Point, whose one crop type has no aspect ratio, get the same
   names as the site's crop types.
3. The width and height in their lowest terms.

The tolerance is `ImageStyleReader::SNAP_TOLERANCE`.

## Other methods

- `cropTypeRatios()`: the aspect ratios of the site's crop types, keyed by
  crop type ID, for the crop types that have one.
- `stylesInUse()`: the IDs of the image styles that a responsive image style
  maps or a view display formatter uses.
- `ratioOf($width, $height, $named)`: the nearest of the given named ratios
  within the tolerance, or the dimensions in their lowest terms.
- `orientationOf($width, $height)`: landscape, portrait, square, freestyle
  for a width with no height, or NULL for no width.

Results of `read()` are kept for the request.
