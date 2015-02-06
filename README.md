# CroppableImage

## Module for ProcessWire 2.5.11+

## Version 0.8.3 Alpha


### How to install

Just add the folder __CroppableImage__ including all of its subfolders and files to your site/modules/ folder. After that go to the modules section in the PW-admin and refresh the list of available modules. You should see three new modules:

* *Fieldtype*CroppableImage
* *Inputfield*CroppableImage
* *Process*CroppableImage

Use __*Fieldtype*CroppableImage__ to install all at once.

After that create a new field and make it use the fieldtype ``CroppableImage``.

With this field you are able to define Cropsettings, but you don't have to. You also can use it like the core Imagefield and just benefit from some enhancements other than the crop-functionality.

If you want to define Cropsettings you can do it for each field in ``Setup > Fields > YOUR_FIELD_NAME`` Input tab:

Each crop setting must be on its own line and consist of at least 3 parameters, separated by comma:

```
crop-name,width,height
```

Optionally you can limit the crop creation and visibility within your input field on page edit to specific templates, separated by comma as well:

```
crop-name,width,height,template-name-1,template-name-2
```

Example:

```
landscape,900,600
portrait,450,600
squarethumb,250,250,home,sidebar
```

### Usage in templates

``FieldtypeCroppableImage`` adds a ``getCrop``-method to ``Pageimage``. ``getCrop`` returns the ``Pageimage`` instance of the crop you have asked for, ie:

```php
// get the first image instance of crop setting 'portrait'
$image = $page->images->first()->getCrop('portrait');
```

You can further use every pageimage property like 'url', 'description', 'width' & 'height' with it:

```php
// get the first image instance of crop setting 'portrait'
$image = $page->images->first()->getCrop('portrait');
echo "<img src='{$image->url}' alt='{$image->description}' />";
```

If you want to manipulate / resize the cropimage further, you should pass a second argument to the ``getCrop-method``. This argument is a PW-Selectorstring. It can contain as many of the known pageimage options like 'quality', 'sharpening', 'cropping', etc, as you need, but none of them is required. But required is at least one setting for 'width' or 'height':

```php
$image = $page->images->first()->getCrop('portrait', "width=200");
$image = $page->images->first()->getCrop('portrait', "width=200, height=200, quality=80");
$image = $page->images->first()->getCrop('portrait', "height=400, sharpening=medium, quality=85");
```

If you haven't cropped that image yet (via the cropping tool), a default cropping from the center will be executed

### Credits

#### Current Version developed by
* Horst Nogajski (@horst-n)
* Christian Raunitschka (@owzim)
* Martijn Geerts (@Da-Fecto)

#### Original Version
* Developed by Antti Peisa (https://twitter.com/apeisa)
* Sponsored by http://stillmovingdesign.com.au/ (thanks Marty!)
* Original GitHub repository https://github.com/apeisa/Thumbnails

### No Warranty
The sofware is provided "as is", use it at your own risk.
