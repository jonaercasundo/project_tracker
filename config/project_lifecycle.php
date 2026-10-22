<?php

return [
    'stages' => [
        'Entry' => ['next' => ['Preparation']],
        'Preparation' => ['next' => ['Submitted']],
        'Submitted' => ['next' => ['Evaluation']],
        'Evaluation' => ['next' => ['Awarded']],
        'Awarded' => ['next' => ['Contract Processing']],
        'Contract Processing' => ['next' => ['Implementation']],
        'Implementation' => ['next' => ['Delivery']],
        'Delivery' => ['next' => ['Billing']],
        'Billing' => ['next' => ['Completed']],
        'Completed' => ['next' => []],
    ],
    'outcomes' => ['Lost', 'Disqualified', 'Cancelled', 'On Hold', 'Rebid'],
    'project_status_mapping' => [
        'Upcoming' => ['stage' => 'Entry'], 'Pre Procurement' => ['stage' => 'Entry'],
        'For Development' => ['stage' => 'Entry'], 'Pending' => ['stage' => 'Entry'],
        'Bidding' => ['stage' => 'Preparation'], 'Bid Evaluation' => ['stage' => 'Evaluation'],
        'Post Qualification' => ['stage' => 'Evaluation'], 'Awarded' => ['stage' => 'Awarded'],
        'For NOA' => ['stage' => 'Awarded'], 'For RTA' => ['stage' => 'Contract Processing'],
        'For Contract Signing' => ['stage' => 'Contract Processing'], 'For NTP' => ['stage' => 'Implementation'],
        'Ongoing' => ['stage' => 'Implementation'], 'Implementation' => ['stage' => 'Implementation'],
        'PDI' => ['stage' => 'Delivery'], 'Delivered' => ['stage' => 'Delivery'],
        'For Billing' => ['stage' => 'Billing'], 'For Collection' => ['stage' => 'Billing'],
        'Collected' => ['stage' => 'Billing'], 'Completed' => ['stage' => 'Completed'],
        'Cancelled' => ['stage' => 'Entry', 'outcome' => 'Cancelled'],
    ],
    'bidding_status_mapping' => [
        'Draft' => ['stage' => 'Preparation'], 'For Review' => ['stage' => 'Preparation'],
        'Published' => ['stage' => 'Submitted'], 'Awarded' => ['stage' => 'Awarded'],
        'Cancelled' => ['stage' => 'Entry', 'outcome' => 'Cancelled'],
        'Completed' => ['stage' => 'Completed'],
    ],
];
