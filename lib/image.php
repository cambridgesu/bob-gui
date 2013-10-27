<?php

# Class to create various image manipulation -related static methods
# Version 1.3.3

# Licence: GPL
# (c) Martin Lucas-Smith, University of Cambridge
# More info: http://download.geog.cam.ac.uk/projects/image/


# Ensure the pureContent framework is loaded and clean server globals
require_once ('pureContent.php');


# Define a class containing image-related static methods
class image
{
	# Function to get a list of images in a directory
	public static function getImageList ($directory)
	{
		# Load the directory support library
		require_once ('directories.php');
		
		# Clean the supplied directory
		$directory = urldecode ($directory);
		
		# Parse the specified directory so that it is always the directory from the server root
		$directory = directories::parse ($directory);
		
		# Define the supported extensions
		$supportedFileTypes = array (/*'gif', */'jpg', 'jpeg', 'png');
		
		# Read the directory, including only supported file types (i.e. extensions)
		$files = directories::listFiles ($directory, $supportedFileTypes);
		
		# Return the list
		return $files;
	}
	
	
	# Function to provide a gallery with comments underneath
	public static function gallery ($captions = array (), $thumbnailsDirectory = 'thumbnails/', $size = 400, $imageGenerator = '/images/generator', $orderByCaptionOrder = false, $exclude = array (), $includeLinkPoints = false)
	{
		# Allow the script to take longer to run (particularly the first time)
		ini_set ('max_execution_time', 120);
		
		# Define the current directory, ensuring it ends with a slash and ensuring that spaces are converted
		$directory = dirname ($_SERVER['REQUEST_URI'] . ((substr ($_SERVER['REQUEST_URI'], -1) == '/') ? 'index.html' : ''));
		if (substr ($directory, -1) != '/') {$directory .= '/';}
		$directory = str_replace ('%20', ' ', $directory);
		
		# If there is a (relative) thumbnail directory, prepend the current directly onto it
		if ($thumbnailsDirectory) {
			if (substr ($thumbnailsDirectory, 0, 1) != '/') {
				$thumbnailsDirectory = $directory . $thumbnailsDirectory;
			}
		}
		
		# Get the list of images in the directory
		$files = image::getImageList ($directory);
		
		# Show a message if there are no files in the directory and exit the function
		if (!$files) {
			return $html = '<p>There are no images to view in this location.</p>';
		}
		
		# Sort the keys, enabling e.g. 030405b.jpg to come before 030405aa.jpg
		uksort ($files, array ('self', 'imageNameSort'));
		
		# Start the HTML block
		$html = "\n\t" . '<div class="gallery">';
		
		# Ensure the thumbnail directory exists if one is required (if not, thumbnails are dynamic and not cached)
		if ($thumbnailsDirectory) {
			$thumbnailServerDirectory = $_SERVER['DOCUMENT_ROOT'] . $thumbnailsDirectory;
			if (!is_dir ($thumbnailServerDirectory) && is_writable ($_SERVER['DOCUMENT_ROOT'] . $directory)) {
				umask (0);
				mkdir ($thumbnailServerDirectory, 0775);
			}
			if (!is_dir ($thumbnailServerDirectory)) {$thumbnailsDirectory = false;}
		}
		
		#!# orderByCaptionOrder not yet supported; here needs to loop through the captions rather than the found images then test for existence
		
		# Loop through each file and construct the HTML
		foreach ($files as $file => $attributes) {
			
			# Skip if excluded
			if (in_array ($file, $exclude)) {continue;}
			
			# Use/create physical thumbnails if required
			if ($thumbnailsDirectory) {
				
				# If there is no thumbnail, make one
				if (!file_exists ($_SERVER['DOCUMENT_ROOT'] . $thumbnailsDirectory . $file)) {
					
					# If there is no image resizing support, say so
					if (!extension_loaded ('gd') || !function_exists ('gd_info')) {
						return $html = '<p class="warning">Error: This server does not appear to support GD2 image resizing and so thumbnails must be created manually.</p>';
					}
					
					# Determine the image location
					$imageLocation = $directory . $file;
					
					# Ensure the image is readable; skip if not
					if (!is_readable ($imageLocation)) {continue;}
					
					# Get the size of the main image
					list ($width, $height) = image::scale ($_SERVER['DOCUMENT_ROOT'] . $imageLocation, $size);
					
					# Attempt to resize; if this fails, do not add the image to the gallery
					if (!image::resize ($_SERVER['DOCUMENT_ROOT'] . $imageLocation, $attributes['extension'], $width, $height, $_SERVER['DOCUMENT_ROOT'] . $thumbnailsDirectory . $file)) {
						continue;
					}
				}
				
				# Get the image size
				list ($width, $height, $type, $imageSize) = getimagesize ($_SERVER['DOCUMENT_ROOT'] . $thumbnailsDirectory . $file);
				
				# Define the link
				$link = '<a href="' . rawurlencode ($file) . '" target="_blank" class="noarrow" rel="lightbox[group]"><img src="' . str_replace (' ', '%20', $thumbnailsDirectory) . rawurlencode ($file) . '" ' . $imageSize . ' alt="Photograph" /></a>';
			} else {
				
				# Get the width of the new image
				list ($width, $height) = image::scale ($_SERVER['DOCUMENT_ROOT'] . $directory . $file, $size);
				
				# Define the link
				$link = '<a href="' . rawurlencode ($file) . '" target="_blank" rel="lightbox[group]"><img src="' . $imageGenerator . '?' . $width . ',' . str_replace (' ', '%20', $directory) . rawurlencode ($file) . '" width="' . $width . '" alt="[Click for full-size image; opens in a new window]" /></a>';
			}
			
			# Define the caption
			if ($captions === true) {
				$caption = '<strong>' . htmlspecialchars ($file) . '</strong> [' . round ($attributes['size'] / '1024', -1) . ' KB]<br />' . strftime ('%a %d/%b/%Y, %l:%M%p', $attributes['time']);
			} else {
				# Set the caption if a comment exists
				$caption = (isSet ($captions[$file]) ? $captions[$file] : '&nbsp;');
			}
			
			# Define the HTML
			#!# Find a more generic way of making id attributes safe
			$id = 'image' . str_replace (array (' ', '+', "'", ), '__', $attributes['name']);
			$html .= "\n" . '
			<div class="image" id="' . $id . '">
				' . $link . '
				<p>' . ($includeLinkPoints ? '<a href="#' . $id . '">#</a> ' : '') . $caption . '</p>
			</div>';
		}
		
		# End the HTML
		$html .= "\n\n\t</div>\n";
		
		# Return the compiled HTML in case that is needed
		return $html;
	}
	
	
	# Function to surround an image with an HTML page
	/* # Use mod_rewrite with something like:
	   # RewriteEngine On
	   # RewriteRule ^/locationOfPagesAndImages/([0-9]+).([0-9]+).html$ /images/pagemaker.html?image=$1.$2.png [passthrough]
	*/
	public static function pagemaker ($root = false)
	{
		# Get the image
		$image = (isSet ($_GET['image']) ? $_GET['image'] : '');
		
		# Get root
		$root = ((substr ($root, -1) == '/') ? substr ($root, 0, -1) : $root);
		
		# Ensure the image type is supported
		if (!preg_match ('/.(jpg|jpeg|gif|png)/', $image)) {
			#!# Change to throwing 404
			echo "<p>\nThat image format is not supported.</p>";
			return false;
		}
		
		# Construct the filename
		$url = dirname ($_SERVER['REQUEST_URI']) . '/' . $image;
		$file = ($root ? $root : $_SERVER['DOCUMENT_ROOT']) . $url;
		
		# If the file does not exist, throw a 404
		if (!file_exists ($file)) {
			#!# Change to throwing 404
			echo "<p>\nThere is no such image.</p>";
			return false;
		}
		
		# Get the image size
		$file = str_replace ('%20', ' ', $file);
		list ($width, $height, $type, $imageSizeHtml) = getimagesize ($file);
		
		# Create the image HTML
		$html = "\n<img src=\"{$url}\" {$imageSizeHtml} alt=\"Image\" />";
		
		# Return the HTML
		return $html;
	}
	
	
	# Helper function to sort by key length
	public static function imageNameSort ($a, $b)
	{
		# If they are the same, return 0 [This should never arise]
		if ($a == $b) {return 0;}
		
		# Validate and obtain matches for a pattern of the (i) 6-digit reverse-date (ii) letter(s) and (iii) [discarded] file extension
		if ((!preg_match ('/([0-9]{6})([a-z]+).(gif|jpg|jpeg|png)/', $a, $matchesA)) || (!preg_match ('/([0-9]{6})([a-z]+).(gif|jpg|jpeg|png)/', $b, $matchesB))) {
			return strcmp ($a, $b);
		}
		
		# Compare the numeric portion
		if ($matchesA[1] < $matchesB[1]) {return -1;}
		if ($matchesA[1] > $matchesB[1]) {return 1;}
		
		# Compare string length
		if (strlen ($matchesA[2]) < strlen ($matchesB[2])) {return -1;}
		if (strlen ($matchesA[2]) > strlen ($matchesB[2])) {return 1;}
		
		# Otherwise compare the strings
		return strcmp ($matchesA[2], $matchesB[2]);
	}
	
	
	# Function to resize an image; supported input and output formats are: jpg, png
	public static function resize ($sourceFileName, $outputFormat = 'jpg', $newWidth = '', $newHeight = '', $outputFile = false, $watermark = false, $inputImageIsServerFullPath = true, $outputImageIsServerFullPath = true)
	{
		# Decode the $sourceFile to remove HTML entities
		$sourceFileName = str_replace ('//', '/', ($inputImageIsServerFullPath ? $sourceFileName : $_SERVER['DOCUMENT_ROOT'] . urldecode ($sourceFileName)));
		if ($outputFile) {
			$outputFile = str_replace ('//', '/', ($outputImageIsServerFullPath ? $outputFile : $_SERVER['DOCUMENT_ROOT'] . urldecode ($outputFile)));
		}
		
		# Check that the file exists and is readable
		if (!file_exists ($sourceFileName)) {
			echo "<p>Error: the selected file ({$sourceFileName}) could not be found.</p>";
			return false;
		}
		
		# Check that the file exists and is readable
		if (!is_readable ($sourceFileName)) {
			echo "<p>Error: the selected file ({$sourceFileName}) could not be read.</p>";
			return false;
		}
		
		# Check that the file is not of zero size
		if (!filesize ($sourceFileName)) {
			// No error message, as this is simply ending early to avoid unnecessary processing
			return true;
		}
		
		# Ensure the output file directory exists if files are being outputted
		if ($outputFile) {
			if (!is_dir ($dirname = dirname ($outputFile))) {
				umask (0);
				mkdir ($dirname, 0775, true);
			}
		}
		
		# Obtain the source image file extension
		$inputFileExtension = strtolower (substr (strrchr ($sourceFileName, '.'), 1));
		$outputFormat = strtolower ($outputFormat);
		$outputFileExtension = $outputFormat;
		
		# Ensure the format is supported
		$supportedExtensions = array ('jpeg', 'gif', 'png');
		if (extension_loaded ('magickwand') || extension_loaded ('imagick')) {$supportedExtensions[] = 'tiff';}	// TIFF support only available in MagickWand/ImageMagick
		if ($inputFileExtension == 'jpg') {$inputFileExtension = 'jpeg';}
		if ($inputFileExtension == 'tif') {$inputFileExtension = 'tiff';}
		if ($outputFormat == 'jpg') {$outputFormat = 'jpeg';}
		if ($outputFormat == 'tif') {$outputFormat = 'tiff';}
		if (!in_array ($inputFileExtension, $supportedExtensions)) {
			echo "\n<p>Error: an unsupported input format ({$inputFileExtension}) was requested.</p>";
			return false;
		}
		if (!in_array ($outputFormat, $supportedExtensions)) {
			echo "\n<p>Error: an unsupported output format ({$outputFormat}) was requested.</p>";
			return false;
		}
		
		# Obtain the height and width of the source image file
		if (!$result = @getimagesize ($sourceFileName)) {
			echo "\n<p>Error: the file could not be read; most likely it is not a valid image.</p>";
			return false;
		}
		list ($originalWidth, $originalHeight, $imageType, $imageAttributes) = $result;
		
		# Ensure that a valid width and height have been entered
		if (!is_numeric ($newWidth) && !is_numeric ($newHeight)) {
			$newWidth = $originalWidth;
			$newHeight = $originalHeight;
		}
		
		# Assign the width and height, proportionally if necessary
		$newWidth = round (is_numeric ($newWidth) ? $newWidth : (($newHeight / $originalHeight) * $originalWidth));
		$newHeight = round (is_numeric ($newHeight) ? $newHeight : (($newWidth / $originalWidth) * $originalHeight));
		
		# Read the exif data if supported
		$exif = false;
/*
		if (function_exists ('exif_read_data')) {
			$exif = exif_read_data ($sourceFileName, 'IFD0');
		}
*/
		
		# Use magickWand/imageMagick in preference to GD if it's available; also essential for TIF format
		if (extension_loaded ('magickwand')) {	// This is the NEW-style imageMagick API, as documented at https://infopol.webassociates.fr/magickwand/docs/
			
			$magickWand = NewMagickWand ();
			MagickReadImage ($magickWand, $sourceFileName);
			$colourspace = MagickGetImageColorspace ($magickWand);
			if ($colourspace == MW_CMYKColorspace) {
				MagickSetImageColorspace ($magickWand, MW_RGBColorspace);
			}
			MagickResizeImage ($magickWand, $newWidth, $newHeight, MW_LanczosFilter, 1);
			MagickSetImageFormat ($magickWand, strtoupper ($outputFormat));
			
			# Add any watermark
			if ($watermark && is_callable ($watermark)) {
				#!# Needs to work for classes - is_callable is basically a mess; no way to do $class::$method in following line
				$watermark ($magickWand /* i.e. handle */, $newHeight);
			}
			
			# Create the image
			if ($outputFile) {
				MagickWriteImage ($magickWand, $outputFile);
			} else {
				header ("Content-Type: image/{$outputFileExtension}");
				MagickEchoImageBlob ($magickWand);
			}
			
		# imageMagick extension
		} else if (extension_loaded ('imagick')) {
			
			$imagick = new Imagick ();
			$imagick->readImage ($sourceFileName);
			$colourspace = $imagick->getImageColorspace ();
			if ($colourspace == imagick::COLORSPACE_CMYK) {
				$imagick->setImageColorspace (imagick::COLORSPACE_RGB);
			}
			$imagick->resizeImage ($newWidth, $newHeight, imagick::FILTER_LANCZOS, 1);
			$imagick->setImageFormat ($outputFormat);
			
			# Add any watermark
			if ($watermark && is_callable ($watermark)) {
				#!# Needs to work for classes - is_callable is basically a mess; no way to do $class::$method in following line
				$watermark ($imagick /* i.e. handle */, $newHeight);
			}
			
			# Create the image
			if ($outputFile) {
				$imagick->writeImage ($outputFile);
			} else {
				header ("Content-Type: image/{$outputFileExtension}");
				echo $imagick->getImageBlob ();
			}
			
		# GD-supported extensions
		} else {
			
			# Determine the function to use and ensure it exists in the PHP installation
			$functionName = 'ImageCreateFrom' . $inputFileExtension;
			if (!function_exists ($functionName)) {
				echo "\n<p>Error: support for generating images from {$inputFileExtension} files is not available on this server.</p>";
				return false;
			}
			
			# Resize the image
			#!# If this line fails because the image is corrupt, then further processing should be stopped
			$sourceFile = $functionName ($sourceFileName);
			$output = ImageCreateTrueColor ($newWidth, $newHeight);
			ImageCopyResampled ($output, $sourceFile, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
			
			# Add any watermark
			if ($watermark && is_callable ($watermark)) {
				#!# Needs to work for classes - is_callable is basically a mess; no way to do $class::$method in following line
				$watermark ($output, $newHeight);
			}
			
			# Determine the function to use and ensure it exists in the PHP installation
			$functionName = 'Image' . $outputFormat;
			if (!function_exists ($functionName)) {
				echo "\n<p>Error: support for generating {$outputFileExtension} files is not available on this server.</p>";
				return false;
			}
			
			# Create the image
			if ($outputFile) {
				$functionName ($output, $outputFile);
			} else {
				header ("Content-Type: image/{$outputFileExtension}");
				$functionName ($output);
			}
		}
		
		# Return true to signal success
		return true;
	}
	
	
	# Function to resize within non-square bounds to within new bounds; basically has the same API as resize
	public static function resizeWithinBoundingBox ($sourceFileName, $outputFormat = 'jpg', $boxWidth, $boxHeight, $outputFile = false, $watermark = false, $inputImageIsServerFullPath = true, $outputImageIsServerFullPath = true)
	{
		# End if not readable
		if (!is_readable ($sourceFileName)) {return false;}
		
		# Set the desired ratio
		$desiredRatio = ($boxWidth / $boxHeight);
		
		# Set the image of the supplied file
		list ($width, $height, $type, $attributes) = getimagesize ($sourceFileName);
		$uploadedRatio = ($width / $height);
		
		# Scale
		if ($uploadedRatio > $desiredRatio) { // i.e. image is wider
			$newWidth = $boxWidth;
			$newHeight = false;	// auto
		} else if ($uploadedRatio < $desiredRatio) { // i.e. image is taller
			$newWidth = false;	// auto
			$newHeight = $boxHeight;
		} else {	// i.e. image is correct ratio
			$newWidth = $boxWidth;
			$newHeight = $boxHeight;
		}
		
		# Resize
		self::resize ($sourceFileName, $outputFormat, $newWidth, $newHeight, $outputFile, $watermark, $inputImageIsServerFullPath, $outputImageIsServerFullPath);
	}
	
	
	# Function to display a gallery of files
	public static function switchableGallery ()
	{
		# Get a listing of files in the current directory (this assumes the current page is called index.html)
		require_once ('directories.php');
		$rawFiles = directories::listFiles ($_SERVER['PHP_SELF']);
		
		# Loop through the list of files
		foreach ($rawFiles as $file => $attributes) {
			
			# List only jpeg files by creating a new array of weeded files
			if ($attributes['extension'] == 'jpg') {
				$files[$file] = $attributes;
			}
		}
		
		# Sort the files alphabetically
		asort ($files);
		
		# Start a variable to hold the HTML
		$html = '';
		
		# Count the number of files and proceed only if there are any
		$totalFiles = count ($files);
		if ($totalFiles > 0) {
			
			# Begin the HTML list
			$jumplist = "\n" . '<p class="jumplist">Go to page:';
			
			# Loop through each file
			$i = 0;
			foreach ($files as $file => $attributes) {
				
				# If the file is the first, store it in memory in case it is needed
				if ($i == 0) {$firstFile[$file] = $attributes;}
				
				# Advance the counter
				$i++;
				
				# Pick out the currently selected image, based on the query string (if any) and assign a CSS flag
				#!# Somehow here need to add class="selected" to the first item when there is no query string or $i is 1?
				$selected = '';
				if ($attributes['name'] == $_SERVER['QUERY_STRING']) {
					$showFile[$file] = $attributes;
					$selected = ' class="selected"';
				} else if ($i == 1) {
					$selected = ' class="first"';
				}
				
				# Add the file to the jumplist of files
				$jumplist .= " <a href=\"?{$attributes['name']}\"$selected>{$attributes['name']}</a>";
			}
			
			# End the HTML link list
			$jumplist .= '</p>';
			
			# Add in the HTML link list
			$html .= $jumplist;
			
			# If no query string was given, or the query string does not match any file, select the first file as the one to be shown
			if (!isSet ($showFile)) {
				$showFile = $firstFile;
			}
			
			# Get the filename
			foreach ($showFile as $name => $attributes) {
				break;
			}
			
			# Get the image size
			list ($width, $height, $type, $htmlAttributes) = getimagesize ($_SERVER['DOCUMENT_ROOT'] . $_SERVER['PHP_SELF'] . $name);
			
			# Show the image
			$html .= "\n\n<img src=\"{$name}\" {$htmlAttributes} title=\"Page {$attributes['name']}\" />";
			
			# Add in the HTML link list again
			$html .= $jumplist;
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to work out the dimensions of a scaled image
	#!# Duplication with scaledImageDimensions
	public static function scale ($file, $size = false)
	{
		# End if the file is readable
		if (!is_readable ($file)) {return array (NULL, NULL);}
		
		# Get the image's height and width
		$result = getimagesize ($file);
		if (empty ($result)) {return array (NULL, NULL);}
		
		# Assign the results
		list ($width, $height, $type, $imageSize) = $result;
		
		# If size is false, use the original
		if (!$size) {return array ($width, $height);}
		
		# Perform the scalings
		if ($width > $height) {
			$scaledWidth = $size;
			$scaledHeight = round ($height * ($scaledWidth / $width));
		} else {
			$scaledHeight = $size;
			$scaledWidth = round ($width * ($scaledHeight / $height));
		}
		
		# Return the width and height
		return array ($scaledWidth, $scaledHeight);
	}
	
	
	# Function to perform image renaming; WARNING: Only use if you know what you're doing - this is quite a specialised function!
	public static function renaming ($directory, $secondsOffset = 21600, $sortByDateNotName = false)
	{
		# Get the files
		$files = image::getImageList ($directory);
		if (!$files) {return false;}
		
		# Get the date for each file
		foreach ($files as $file => $attributes) {
			$sortedFiles[$file] = $attributes['time'];
		}
		
		# Sort by date/time if necessary
		if ($sortByDateNotName) {asort ($sortedFiles);}
		
		# Assign the date for each
		foreach ($sortedFiles as $file => $timestamp) {
			
			# Offset the time, so that e.g. for 21600, a new day 'starts' at 6am (21600 seconds past midnight)
			$timestamp -= $secondsOffset;
			
			# Assign the file date
			$fileDate = date ('ymd', $timestamp);
			
			# Start an entry for this date if not already present, or increment the character if not
			if (!isSet ($assignedNames[$fileDate])) {
				$assignedNames[$fileDate] = 'a';
			} else {
				$assignedNames[$fileDate]++;
			}
			
			# Convert the date tally to alphanumeric
			$base = 26;
			$set = floor ($assignedNames[$fileDate] / $base);
			$setPrefix = ($set ? chr (96 + $set) : '');
			
			# Construct the file extension
			$extension = '.' . strtolower ($files[$file]['extension']);
			
			# Construct the filename
			$renamedFiles[$file] = $fileDate . $setPrefix . $assignedNames[$fileDate] . $extension;
		}
		
		# Rename each file, or stop if there is a problem
		foreach ($renamedFiles as $old => $new) {
			if (!rename ($directory . $old, $directory . $new)) {return false;}
			echo "\nSuccessfully renamed: {$directory}<strong>{$old}</strong> &raquo; {$directory}<strong>{$new}</strong><br />";
		}
	}
	
	
	# Function to return the image dimensions of an image when scaled
	#!# Duplication with scale
	public static function scaledImageDimensions ($width, $height, $maximumDimension)
	{
		# Ensure the height and maximum dimension is legal or stop execution
		if (!is_numeric ($maximumDimension) || $maximumDimension == 0) {return false;}
		if (!is_numeric ($height) || $height == 0) {return false;}
		
		# Compute the new height and width, scaling down only if the original is greater than the base size
		$ratio = ($width / $height);
		if ($width > $height) {
			if ($width > $maximumDimension) {
				$width = $maximumDimension;
				$height = $width / $ratio;
			}
		} else {
			if ($height > $maximumDimension) {
				$height = $maximumDimension;
				$width = $height * $ratio;
			}
		}
		$width = round ($width);
		$height = round ($height);
		
		# Return the new width and height
		return array ($width, $height);
	}
	
	
	# Function to deal with uploaded images
	#!# Ideally integrate into ultimateForm
	public static function resizeAndReformat ($image, $imageStoreRoot, $outputName /*= false*/, $imageMaxSize, $imageOutputFormat /*, $supportedImageExtensions */)
	{
		# Ensure there is an image and that it exists and is readable
		if ($image && is_readable ($imageStoreRoot . trim ($image))) {
			list ($width, $height, $type, $attributes) = getimagesize ($imageStoreRoot . $image);
			
			# Perform resizing if the image width/height/format is not compliant
			if (($width > $imageMaxSize) || ($height > $imageMaxSize) || (substr ($imageStoreRoot . $image, (0 - strlen ('.' . $imageOutputFormat))) != ('.' . $imageOutputFormat))) {
				$newWidth = ($width > $imageMaxSize ? $imageMaxSize : $width);
				$inputFile = $imageStoreRoot . $image;
				//$outputFile = $imageStoreRoot . ($outputName ? $outputName : preg_replace ('/(' . implode ('|', $supportedImageExtensions) . ')$/', ".{$imageOutputFormat}", $image));
				$outputFile = $imageStoreRoot . $outputName;
				image::resize ($imageStoreRoot . $image, $imageOutputFormat, $newWidth, $newHeight = '', $outputFile);
				
				# Remove the old file if it's a different file extension
				if ($inputFile != $outputFile) {
					unlink ($inputFile);
				}
			}
		}
	}
	
	
	# Function to define the HTML for an image where the extension is not certain; NB $imagesLocation is slash-terminated
	#!# This is really not very efficient. It might be better to convert and archive off old files
	public static function fnmatchImage ($itemBasename, $imagesLocation, $supportedImageExtensions = array ('.jpg', '.gif', '.jpeg', '.png', '.JPG', '.GIF', '.JPEG', '.PNG', ))
	{
		# Start with no file found
		$location = false;
		
		# Find the most recent file which complies with the file extension rules
		$latestFilemtime = 0;
		foreach ($supportedImageExtensions as $supportedImageExtension) {
			$tryLocation = $imagesLocation . $itemBasename . $supportedImageExtension;
			$imageOnServer = $_SERVER['DOCUMENT_ROOT'] . $tryLocation;
			if (file_exists ($imageOnServer)) {
				$filemtime = filemtime ($imageOnServer);
				if ($filemtime > $latestFilemtime) {
					$latestFilemtime = $filemtime;
					$location = $tryLocation;
				}
			}
		}
		
		# Return the chosen location
		return $location;
	}
	
	
	# Function to construct an image tag with sizes computed
	public static function imgTag ($location, $altText = 'Image', $align = false, $preventCaching = true)
	{
		# Return empty string if no location
		if (!$location) {return '';}
		
		# Get the HTML size attributes
		if (preg_match ('@^(http|https)://@i', $location)) {
			$attributes = '';	// Disabled for remote images, as this causes a noticable page delay (and there is the potential for a DoS if the image size is large)
		} else {
			list ($width, $height, $type, $attributes) = getimagesize ($_SERVER['DOCUMENT_ROOT'] . $location);
			$attributes = ' ' . $attributes;
		}
		
		# Compile the HTML; the random number is added to prevent caching
		$html = '<img alt="' . htmlspecialchars ($altText) . '" title="' . htmlspecialchars ($altText) . '" src="' . $location . ($preventCaching ? '?' . rand (1, 999) : '') . '"' . $attributes . ($align ? " align=\"{$align}\"" : '') . ' />';
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to serve an image file file rather than directly access it through a URL
	public static function serve ($file)
	{
		# Ensure the extension is valid
		$extension = strtolower (pathinfo ($file, PATHINFO_EXTENSION));
		$validExtensions = array (
			'jpg' => 'image/jpeg',
			'png' => 'image/png',
			'gif' => 'image/gif',
			'tif' => 'image/tif',
		);
		if (!array_key_exists ($extension, $validExtensions)) {
			#!# Probably should be access denied
			header ('HTTP/1.0 404 Not Found');
			return false;
		}
		
		# Check the file is readable
		if (!is_readable ($file)) {
			header ('HTTP/1.0 404 Not Found');
			return false;
		}
		
		# Send the file, with the correct header
		header ("content-type: {$validExtensions[$extension]}"); 
		readfile ($file);
	}
}






/************************************************************\

    IPTC EASY 1.0 - IPTC data manipulator for JPEG images
        
    All reserved www.image-host-script.com
    
    Sep 15, 2008

\************************************************************/

DEFINE('IPTC_OBJECT_NAME', '005');
DEFINE('IPTC_EDIT_STATUS', '007');
DEFINE('IPTC_PRIORITY', '010');
DEFINE('IPTC_CATEGORY', '015');
DEFINE('IPTC_SUPPLEMENTAL_CATEGORY', '020');
DEFINE('IPTC_FIXTURE_IDENTIFIER', '022');
DEFINE('IPTC_KEYWORDS', '025');
DEFINE('IPTC_RELEASE_DATE', '030');
DEFINE('IPTC_RELEASE_TIME', '035');
DEFINE('IPTC_SPECIAL_INSTRUCTIONS', '040');
DEFINE('IPTC_REFERENCE_SERVICE', '045');
DEFINE('IPTC_REFERENCE_DATE', '047');
DEFINE('IPTC_REFERENCE_NUMBER', '050');
DEFINE('IPTC_CREATED_DATE', '055');
DEFINE('IPTC_CREATED_TIME', '060');
DEFINE('IPTC_ORIGINATING_PROGRAM', '065');
DEFINE('IPTC_PROGRAM_VERSION', '070');
DEFINE('IPTC_OBJECT_CYCLE', '075');
DEFINE('IPTC_BYLINE', '080');
DEFINE('IPTC_BYLINE_TITLE', '085');
DEFINE('IPTC_CITY', '090');
DEFINE('IPTC_PROVINCE_STATE', '095');
DEFINE('IPTC_COUNTRY_CODE', '100');
DEFINE('IPTC_COUNTRY', '101');
DEFINE('IPTC_ORIGINAL_TRANSMISSION_REFERENCE',     '103');
DEFINE('IPTC_HEADLINE', '105');
DEFINE('IPTC_CREDIT', '110');
DEFINE('IPTC_SOURCE', '115');
DEFINE('IPTC_COPYRIGHT_STRING', '116');
DEFINE('IPTC_CAPTION', '120');
DEFINE('IPTC_LOCAL_CAPTION', '121');

class iptc {
    var $meta=Array();
    var $hasmeta=false;
    var $file=false;
    
    
    function iptc($filename) {
        $size = getimagesize($filename,$info);
        $this->hasmeta = isset($info["APP13"]);
        if($this->hasmeta)
            $this->meta = iptcparse ($info["APP13"]);
        $this->file = $filename;
    }
    function set($tag, $data) {
        $this->meta ["2#$tag"]= Array( $data );
        $this->hasmeta=true;
    }
    function get($tag) {
        return isset($this->meta["2#$tag"]) ? $this->meta["2#$tag"][0] : false;
    }
    
    function dump() {
        print_r($this->meta);
    }
    function binary() {
        $iptc_new = '';
        foreach (array_keys($this->meta) as $s) {
            $tag = str_replace("2#", "", $s);
            $iptc_new .= $this->iptc_maketag(2, $tag, $this->meta[$s][0]);
        }        
        return $iptc_new;    
    }
    function iptc_maketag($rec,$dat,$val) {
        $len = strlen($val);
        if ($len < 0x8000) {
               return chr(0x1c).chr($rec).chr($dat).
               chr($len >> 8).
               chr($len & 0xff).
               $val;
        } else {
               return chr(0x1c).chr($rec).chr($dat).
               chr(0x80).chr(0x04).
               chr(($len >> 24) & 0xff).
               chr(($len >> 16) & 0xff).
               chr(($len >> 8 ) & 0xff).
               chr(($len ) & 0xff).
               $val;
               
        }
    }    
    function write() {
        if(!function_exists('iptcembed')) return false;
        $mode = 0;
        $content = iptcembed($this->binary(), $this->file, $mode);    
        $filename = $this->file;
            
        @unlink($filename); #delete if exists
        
        $fp = fopen($filename, "w");
        fwrite($fp, $content);
        fclose($fp);
    }    
    
    #requires GD library installed
    function removeAllTags() {
        $this->hasmeta=false;
        $this->meta=Array();
        $img = imagecreatefromstring(implode(file($this->file)));
        @unlink($this->file); #delete if exists
        imagejpeg($img,$this->file,100);
    }
}

/*
// Update copyright statement:
$i = new iptc("test.jpg");
echo $i->set(IPTC_COPYRIGHT_STRING,"Here goes the new data"); 
$i->write();

// Example read copyright string:
$i = new iptc("test.jpg");
echo $i->get(IPTC_COPYRIGHT_STRING); 
*/



?>