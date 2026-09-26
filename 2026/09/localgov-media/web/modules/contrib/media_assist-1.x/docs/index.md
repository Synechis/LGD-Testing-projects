# Media Assist

Three modules that show editors what a site's image styles do to their
pictures.

**media_assist** provides `ImageStyleReader`, which reads an image style's
crop type, output width and height, and the aspect ratio they come to. The
other two modules depend on it. It does nothing on its own.

**media_assist_crop** labels every cropped image on a page with the shape and
box it was rendered at, keeps a live "n of m crops set" summary above the
crop widget on the image media form, and adds a Crop preview tab to each
image media item showing the real derivative at every shape, unset ones
first. [Labels, summary and crop preview](crop.md).

**media_assist_embed** replaces the list of view mode names in the CKEditor
media balloon with two controls, Shape and Size. Picking a shape keeps the
size and picking a size keeps the shape. [Shape and size in
CKEditor](embed.md).

Image Widget Crop and Focal Point are both supported. [How image styles are
read](image-style-reader.md) says how a style becomes a shape.

Neither module stores configuration. [Installing](installing.md).
