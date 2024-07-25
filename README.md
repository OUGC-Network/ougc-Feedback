## OUGC Feedback

Adds a powerful feedback system to your forum.

***

### Support

Please visit [OUGC Network](https://ougc.network/ "Visit OUGC Network") for more information about this project.

### Thank You!

Remember this is a free release developed on free time, either for personal use or as custom requests.

Any contribution is welcome.

Thanks for downloading and using my plugins, I really appreciate it!

        require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
        find_replace_templatesets(
            'member_profile',
            '#' . preg_quote('{$profilefields}') . '#i',
            '{$profilefields}{$ougc_feedback}'
        );
        find_replace_templatesets(
            'postbit',
            '#' . preg_quote('{$post[\'button_rep\']}') . '#i',
            '{$post[\'button_rep\']}{$post[\'ougc_feedback_button\']}'
        );
        find_replace_templatesets(
            'postbit_classic',
            '#' . preg_quote('{$post[\'button_rep\']}') . '#i',
            '{$post[\'button_rep\']}{$post[\'ougc_feedback_button\']}'
        );
        find_replace_templatesets(
            'postbit_author_user',
            '#' . preg_quote('{$post[\'warninglevel\']}') . '#i',
            '{$post[\'warninglevel\']}<!--OUGC_FEEDBACK-->'
        );
        //find_replace_templatesets('memberlist_user', '#'.preg_quote('{$referral_bit}').'#i', '{$referral_bit}{$ougc_feedback_bit}');
        //find_replace_templatesets('memberlist', '#'.preg_quote('{$referral_header}').'#i', '{$referral_header}{$ougc_feedback_header}');
        //find_replace_templatesets('memberlist', '#'.preg_quote('{$lang->sort_by_referrals}</option>').'#i', '{$lang->sort_by_referrals}</option>{$ougc_feedback_sort}');
        find_replace_templatesets(
            'headerinclude',
            '#' . preg_quote('{$stylesheets}') . '#i',
            '{$stylesheets}{$ougc_feedback_js}'
        );






        require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
        find_replace_templatesets('member_profile', '#' . preg_quote('{$ougc_feedback}') . '#i', '', 0);
        find_replace_templatesets('postbit', '#' . preg_quote('{$post[\'ougc_feedback_button\']}') . '#i', '', 0);
        find_replace_templatesets(
            'postbit_classic',
            '#' . preg_quote('{$post[\'ougc_feedback_button\']}') . '#i',
            '',
            0
        );
        find_replace_templatesets('postbit_author_user', '#' . preg_quote('<!--OUGC_FEEDBACK-->') . '#i', '', 0);
        //find_replace_templatesets('memberlist_user', '#'.preg_quote('{$ougc_feedback_bit}').'#i', '', 0);
        //find_replace_templatesets('memberlist', '#'.preg_quote('{$ougc_feedback_header}').'#i', '', 0);
        //find_replace_templatesets('memberlist', '#'.preg_quote('{$ougc_feedback_sort}').'#i', '', 0);
        find_replace_templatesets('headerinclude', '#' . preg_quote('{$ougc_feedback_js}') . '#i', '', 0);
        //find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'ougc_feedback\']}').'#i', '', 0);
        //find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'ougc_feedback\']}').'#i', '', 0);