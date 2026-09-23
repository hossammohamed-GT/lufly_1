<?php

declare(strict_types=1);

/*
 * The finder: "I am looking for something like this".
 *
 * Every line here is read by a visitor — the chat shell, the answers, and the
 * promise that a piece we do not show yet is still a piece we can get.
 */
return [
    /* the chat shell */
    'title' => 'Find a piece',
    'subtitle' => 'Describe it, or send a photo',
    'open' => 'Find me a piece',
    'open_hint' => 'Words or a photo',
    'close' => 'Close the chat',
    'grow' => 'Make the window bigger',
    'shrink' => 'Back to the small window',
    'resize' => 'Drag to resize the window',
    'foot' => 'Our own catalogue · no account needed',
    'tab_find' => 'Find a piece',
    'tab_planner' => 'Bathroom plan',

    /* the input */
    'placeholder' => 'e.g. a small oval washbasin, chrome, 55 cm…',
    'send' => 'Send',
    'attach' => 'Send a photo',
    'attach_hint' => 'One picture — the piece you have in mind',
    'photo_ready' => 'Photo attached',
    'remove_photo' => 'Remove the photo',
    'chips_label' => 'Or start from one of these',
    'chip_like' => 'Like the piece I am looking at',
    'chip_1' => 'A wall-hung toilet',
    'chip_2' => 'A small washbasin for a guest bathroom',
    'chip_3' => 'A chrome shower set',
    'chip_4' => 'Something for a wheelchair-accessible bathroom',

    /* the address, asked once before the first answer */
    'ask_title' => 'Before we start',
    'ask_text' => 'Leave your e-mail and our team can follow up. With a photo we always look at it ourselves and answer you directly.',
    'ask_label' => 'Your e-mail',
    'ask_placeholder' => 'you@example.com',
    'ask_start' => 'Start',
    'ask_skip' => 'Continue without it',
    'ask_saved' => 'Thank you — ask away.',

    /* answers */
    'found_1' => 'I found :n pieces close to what you described.',
    'found_2' => 'Here are :n pieces from our catalogue that answer to that.',
    'found_3' => ':n pieces worth a look — the closest first.',
    'loose_1' => 'Nothing exact in the catalogue yet, so here are the closest :n we have.',
    'loose_2' => 'I could not match that word for word — these :n come closest.',
    'found_photo' => 'That looks like a piece we carry. Here are :n matches.',
    'found_photo_loose' => 'I had a good look at your photo — these :n are the closest we have.',
    'found_like' => 'Here are :n pieces from the same family as the one you are looking at.',
    'nothing' => 'I could not match that to anything we have online.',
    'popular_1' => 'Not one word of that is in our catalogue yet. So you can see how we work, here is what our visitors come back for:',
    'popular_2' => 'I could not match that to anything we have online — but the team can, and here is a taste of the shop:',
    'need_words' => 'Write a few words about the piece, or send a photo — then I can look.',
    'over_limit' => 'That is a lot of questions for one day. Write to us and the team takes it from here.',
    'err' => 'Something went wrong on our side. Please try again in a moment.',
    'off' => 'The finder is switched off right now.',
    'photo_off' => 'Sending photos is switched off right now — a description works just as well.',

    /* the lines under the cards */
    'note_sent' => 'Your request is with our team — with your e-mail they can answer you directly.',
    'note_kept' => 'I kept your photo for the team: they see it in the site and come back with the closest pieces.',
    'note_team' => 'A piece like that is very probably in our warehouse but not on the website yet — write to us, or leave your e-mail, and the team will find it for you.',
    'note_photo_rejected' => 'That photo did not come through (too big, or not a picture we can read). Try another one, or describe the piece in words.',
    'results' => 'Closest pieces',
    'see_all' => 'See all results in the catalogue',
    'support_call' => 'Talk to the team',
    'support_mail' => 'Write to us',
    'support_whatsapp' => 'Send on WhatsApp',

    /* while it works */
    'waiting_1' => 'Reading your words…',
    'waiting_2' => 'Looking through our catalogue…',
    'waiting_3' => 'Comparing sizes and finishes…',
    'waiting_4' => 'Almost there…',

    /* the planner tab — promised, not delivered yet */
    'soon_pill' => 'Coming soon',
    'soon_title' => 'The bathroom planner is on its way',
    'soon_text' => 'Room size in, a whole plan out: what to install, in which size, where each piece goes, and a drawing you can hand to your plumber. We are still building it.',
    'soon_note' => 'Until it is ready, describe what you need in the finder or send a photo — we will match it against our catalogue right away.',

    /* the same tab once the planner really opens */
    'plan_open_title' => 'Plan your bathroom',
    'plan_open_text' => 'Three short questions and you have a plan you can hand to your plumber: what to install, in which size, where each piece goes.',

    /* the mail the team gets */
    'mail_subject' => 'New finder request #:n',
];
