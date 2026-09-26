# Installing

Place the package at `modules/contrib/media_assist`, then:

    drush en media_assist_crop media_assist_embed

Either module can be installed without the other. `media_assist` is installed
with them.

## Requirements

- media_assist: image.
- media_assist_crop: image, media, and the crop module. The summary above the
  widget needs Image Widget Crop; the labels and the preview work with Image
  Widget Crop or Focal Point.
- media_assist_embed: breakpoint, ckeditor5, filter, image, media. The Shape
  and Size controls appear in text formats whose Media Embed filter is
  enabled.

## Permissions

- *See which image style rendered an image*: the labels on pages.
- *See which crops are set on a media image*: the summary on the media form
  and the Crop preview tab. The tab also needs permission to edit the media
  item.

## Uninstalling

    drush pmu media_assist_crop media_assist_embed media_assist

Nothing is left behind. Content embedded through the CKEditor controls stores
the view mode it was given, which the media embed filter renders with or
without the module.
