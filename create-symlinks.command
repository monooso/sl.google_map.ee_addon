#!/bin/bash

# This script creates symlinks from the addon files and directories to the appropriate locations in the EE installation.
# It enables us to keep the addon in a single folder in the root of the site, and keep it version-controlled, separate
# from the main website.

addon_dir_path=`pwd`

echo "Enter the path to your ExpressionEngine installation, without a trailing slash (e.g. /var/www/html/mysite.com), and press ENTER:"
read ee_path
echo "Enter your 'system' folder name, and press ENTER:"
read ee_system_folder

# Delete any existing symlinks.
if [ -e "$ee_path"/"$ee_system_folder"/extensions/fieldtypes/sl_google_map ]
	then
		rm -R "$ee_path"/"$ee_system_folder"/extensions/fieldtypes/sl_google_map
fi

if [ -e "$ee_path"/"$ee_system_folder"/language/english/lang.sl_google_map.php ]
	then
		rm "$ee_path"/"$ee_system_folder"/language/english/lang.sl_google_map.php
fi

# Create the symlinks.
ln -s "$addon_dir_path"/system/extensions/fieldtypes/sl_google_map "$ee_path"/"$ee_system_folder"/extensions/fieldtypes/sl_google_map
ln -s "$addon_dir_path"/system/language/english/lang.sl_google_map.php "$ee_path"/"$ee_system_folder"/language/english/lang.sl_google_map.php
