<?php

/**
 * Default WhatsApp copy for review invites and one-shot reminders.
 * Artisans can override these on their profile. Placeholders:
 * {client_name}  — greeting name, or empty
 * {greeting}     — "Hi Ada," or "Hi,"
 * {job}          — short review phrase (“rewiring”)
 * {link}         — public review URL
 */
return [
    'invite' => '{greeting} thanks for trusting me with your {job}! '
        ."I'd really appreciate it if you could leave a quick review here: {link} "
        .'— it only takes a minute, and it helps others know they can trust my work too.',

    'reminder' => '{greeting} just a gentle reminder about the review for your {job}: {link} '
        .'— whenever you have a minute. Thank you!',

    // How many days after the first invite before a reminder becomes due.
    'default_reminder_days' => 3,

    'max_template_length' => 700,
];
