# Joomla-Extension-Live-Updater-Plugin
https://www.web-expert.gr/en/joomla-extensions/item/104-joomla-extension-live-updater-plugin

It's free and always will be. You can use only one plugin for ALL of your extensions!

The problem:

Many developers daily are creating and maintaining dozens of Joomla Extensions, most of them like me, we don't have added auto-update feature in our paid extensions (really), probably because the Joomla Update system event is too poor.

Most developers create a plugin per extension to able to accomplish the auto-update feature. Is not bad but it's not the best way. Why clients & developers have to maintain so many extensions for a single-simple task?

The Solution:

This plugin will handle ALL auto-update extensions (UNIVERSAL SULUTION), the only thing you have to do is to add some parameters to your update XML in "downloads" tags

 If you had something like that

downloadurl type="full" format="zip"><![CDATA[https://mysite.com/index.php?option=com_ars&view=release&id=12&format=raw]]

replace it with
downloadurl type="full" format="zip"><![CDATA[https://mysite.com/index.php?option=com_ars&view=release&id=12&format=raw&source=JExtUpdater&license=paid&type=component&extname=example]]

The magic world is "=JExtUpdater"

For more advanced checks we added a new event (onUpdateBeforePackageDownload) is fired for any plugin type but also for Module & Component!

Read the documentation for more details. It's too easy! Actually does not require any extra coding for your extension!

For developers:

   Download the documentation https://www.web-expert.gr/clients/dl.php?type=d&id=117
   Discusion on forum.joomla.org  https://forum.joomla.org/viewtopic.php?f=715&t=945311
   JED Listing (soon link)
