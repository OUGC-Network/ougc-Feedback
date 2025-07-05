<p align="center">
    <a href="" rel="noopener">
        <img width="700" height="400" src="https://github.com/user-attachments/assets/1c07cf59-d844-4fd6-bbb1-4349f1432f71" alt="Project logo">
    </a>
</p>

<h3 align="center">ougc Feedback</h3>

---

<p align="center"> Adds a powerful feedback system to your forum.
    <br> 
</p>

## 📜 Table of Contents <a name = "table_of_contents"></a>

- [About](#about)
- [Getting Started](#getting_started)
    - [Dependencies](#dependencies)
    - [File Structure](#file_structure)
    - [Install](#install)
    - [Update](#update)
    - [Template Modifications](#template_modifications)
- [Settings](#settings)
    - [File Level Settings](#file_level_settings)
- [Templates](#templates)
- [Usage](#usage)
- [Built Using](#built_using)
- [Authors](#authors)
- [Acknowledgments](#acknowledgement)
- [Support & Feedback](#support)

## 🚀 About <a name = "about"></a>

...

[Go up to Table of Contents](#table_of_contents)

## 📍 Getting Started <a name = "getting_started"></a>

The following information will assist you into getting a copy of this plugin up and running on your forum.

### Dependencies <a name = "dependencies"></a>

A setup that meets the following requirements is necessary to use this plugin.

- [MyBB](https://mybb.com/) >= 1.8
- PHP >= 7
- [MyBB-PluginLibrary](https://github.com/frostschutz/MyBB-PluginLibrary) >= 13

### File structure <a name = "file_structure"></a>

  ```
   .
   ├── inc
   │ ├── languages
   │ │ ├── english
   │ │ │ ├── ougc_feedback.lang.php
   │ │ │ ├── admin
   │ │ │ │ ├── ougc_feedback.lang.php
   │ ├── plugins
   │ │ ├── ougc
   │ │ │ ├── Feedback
   │ │ │ │ ├── hooks
   │ │ │ │ │ ├── admin.php
   │ │ │ │ │ ├── forum.php
   │ │ │ │ ├── templates
   │ │ │ │ │ ├── form
   │ │ │ │ │ ├── form_comment
   │ │ │ │ │ ├── js
   │ │ │ │ │ ├── memberlist
   │ │ │ │ │ ├── memberlist_average
   │ │ │ │ │ ├── memberlist_view_all
   │ │ │ │ │ ├── modal
   │ │ │ │ │ ├── modal_error
   │ │ │ │ │ ├── modal_tfoot
   │ │ │ │ │ ├── page
   │ │ │ │ │ ├── page_addlink
   │ │ │ │ │ ├── page_empty
   │ │ │ │ │ ├── page_item
   │ │ │ │ │ ├── page_item_delete
   │ │ │ │ │ ├── page_item_delete_hard
   │ │ │ │ │ ├── page_item_edit
   │ │ │ │ │ ├── page_item_report
   │ │ │ │ │ ├── page_item_restore
   │ │ │ │ │ ├── postbit
   │ │ │ │ │ ├── postbit_average
   │ │ │ │ │ ├── postbit_button
   │ │ │ │ │ ├── postbit_view_all
   │ │ │ │ │ ├── profile
   │ │ │ │ │ ├── profile_add
   │ │ │ │ │ ├── profile_average
   │ │ │ │ │ ├── profile_latest
   │ │ │ │ │ ├── profile_latest_empty
   │ │ │ │ │ ├── profile_latest_row
   │ │ │ │ │ ├── profile_latest_view_all
   │ │ │ │ │ ├── profile_view_all
   │ │ │ │ ├── settings.json
   │ │ │ │ ├── admin.php
   │ │ │ │ ├── classes.php
   │ │ │ │ ├── core.php
   ├── jscripts
   │ ├── ougc_feedback.js
   ```

### Installing <a name = "install"></a>

Follow the next steps in order to install a copy of this plugin on your forum.

1. Download the latest package from one of the following sources:
    - ...
2. Upload the contents of the _Upload_ folder to your MyBB root directory.
3. Browse to _Configuration » Plugins_ and install this plugin by clicking _Install & Activate_.

### Updating <a name = "update"></a>

Follow the next steps in order to update your copy of this plugin.

1. Browse to _Configuration » Plugins_ and deactivate this plugin by clicking _Deactivate_.
2. Follow step 1 and 2 from the [Install](#install) section.
3. Browse to _Configuration » Plugins_ and activate this plugin by clicking _Activate_.

### Template Modifications <a name = "template_modifications"></a>

The following template edits are required for this plugin to work.

1. Insert `{$ougc_feedback}` after `{$profilefields}` inside the `member_profile` template.
2. Insert `{$post['ougc_feedback_button']}` after `{$post['button_rep']}` inside the `postbit` template.
3. Insert `{$post['ougc_feedback_button']}` after `{$post['button_rep']}` inside the `postbit_classic` template.
4. Insert `<!--OUGC_FEEDBACK-->` after `{$post['warninglevel']}` inside the `postbit_author_user` template.
5. Insert `{$user['feedback']}` inside the `memberlist_user` template.
6. Insert `{$ougc_feedback_header}` after `{$referral_header}` inside the `memberlist` template.
7. Insert `{$ougc_feedback_sort}` after `{$referrals_option}` inside the `memberlist` template.
8. Insert `{$ougc_feedback_js}` after `{$stylesheets}` inside the `headerinclude` template.
9. Insert `{$feedbackLatest}` inside the `member_profile` template.

[Go up to Table of Contents](#table_of_contents)

## 🛠 Settings <a name = "settings"></a>

Below you can find a description of the plugin settings.

### Global Settings

...

### File Level Settings <a name = "file_level_settings"></a>

Additionally, you can force your settings by updating the `SETTINGS` array constant in
the `ougc\Feedback\Core` namespace in the `./inc/plugins/ougc_feedback.php` file. Any setting set
this way will always bypass any front-end configuration. Use the setting key as shown below:

```PHP
define('ougc\Feedback\Core\SETTINGS', [
    'key' => 'value',
]);
```

[Go up to Table of Contents](#table_of_contents)

## 📐 Templates <a name = "templates"></a>

The following is a list of templates available for this plugin.

...

[Go up to Table of Contents](#table_of_contents)

## 📖 Usage <a name="usage"></a>

This plugin has no additional configurations; after activating make sure to modify the global settings in order to get
this plugin working.

[Go up to Table of Contents](#table_of_contents)

## ⛏ Built Using <a name = "built_using"></a>

- [MyBB](https://mybb.com/) - Web Framework
- [MyBB PluginLibrary](https://github.com/frostschutz/MyBB-PluginLibrary) - A collection of useful functions for MyBB
- [PHP](https://www.php.net/) - Server Environment

[Go up to Table of Contents](#table_of_contents)

## ✍️ Authors <a name = "authors"></a>

- [@Omar G](https://github.com/Sama34) - Idea & Initial work

[Go up to Table of Contents](#table_of_contents)

## 🎉 Acknowledgements <a name = "acknowledgement"></a>

- [The Documentation Compendium](https://github.com/kylelobo/The-Documentation-Compendium)

[Go up to Table of Contents](#table_of_contents)

## 🎈 Support & Feedback <a name="support"></a>

This is free development and any contribution is welcome. Get support or leave feedback at the
official [MyBB Community](https://community.mybb.com/thread-159249.html).

Thanks for downloading and using our plugins!

[Go up to Table of Contents](#table_of_contents)