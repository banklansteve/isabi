<?php

/**
 * Trade credentials commonly held by Nigerian artisans.
 *
 * These are SELF-DECLARED. Isabi does not verify any of them, and the public
 * page must always label them as such. The catalogue exists only to give
 * artisans accurate names and issuing bodies to pick from instead of
 * free-typing something a client can't recognise.
 */
return [
    'max' => 6,

    'groups' => [
        [
            'label' => 'Government trade tests',
            'hint' => 'The standard proficiency tests for Nigerian artisans.',
            'items' => [
                [
                    'title' => 'Trade Test Grade I',
                    'issuer' => 'Federal Ministry of Labour & Employment',
                ],
                [
                    'title' => 'Trade Test Grade II',
                    'issuer' => 'Federal Ministry of Labour & Employment',
                ],
                [
                    'title' => 'Trade Test Grade III',
                    'issuer' => 'Federal Ministry of Labour & Employment',
                ],
            ],
        ],
        [
            'label' => 'Technical & vocational certificates',
            'hint' => 'Awarded after formal technical training.',
            'items' => [
                [
                    'title' => 'National Technical Certificate (NTC)',
                    'issuer' => 'NABTEB',
                ],
                [
                    'title' => 'Advanced National Technical Certificate (ANTC)',
                    'issuer' => 'NABTEB',
                ],
                [
                    'title' => 'National Skills Qualification — Level 1',
                    'issuer' => 'NBTE',
                ],
                [
                    'title' => 'National Skills Qualification — Level 2',
                    'issuer' => 'NBTE',
                ],
                [
                    'title' => 'National Skills Qualification — Level 3',
                    'issuer' => 'NBTE',
                ],
                [
                    'title' => 'National Skills Qualification — Level 4',
                    'issuer' => 'NBTE',
                ],
                [
                    'title' => 'ITF Training Certificate',
                    'issuer' => 'Industrial Training Fund',
                ],
                [
                    'title' => 'National Diploma (OND)',
                    'issuer' => 'Nigerian Polytechnic',
                ],
                [
                    'title' => 'Higher National Diploma (HND)',
                    'issuer' => 'Nigerian Polytechnic',
                ],
                [
                    'title' => 'City & Guilds Certificate',
                    'issuer' => 'City & Guilds',
                ],
            ],
        ],
        [
            'label' => 'Professional licences & registration',
            'hint' => 'Regulated bodies that license practitioners.',
            'items' => [
                [
                    'title' => 'NEMSA Electrical Licence',
                    'issuer' => 'Nigerian Electricity Management Services Agency',
                ],
                [
                    'title' => 'COREN Registration',
                    'issuer' => 'Council for the Regulation of Engineering in Nigeria',
                ],
                [
                    'title' => 'CORBON Registration',
                    'issuer' => 'Council of Registered Builders of Nigeria',
                ],
                [
                    'title' => 'NIOB Membership',
                    'issuer' => 'Nigerian Institute of Building',
                ],
                [
                    'title' => 'SON Product Certification',
                    'issuer' => 'Standards Organisation of Nigeria',
                ],
                [
                    'title' => 'NAFDAC Registration',
                    'issuer' => 'NAFDAC',
                ],
            ],
        ],
        [
            'label' => 'Business & association',
            'hint' => 'Registration and trade group membership.',
            'items' => [
                [
                    'title' => 'CAC Business Registration',
                    'issuer' => 'Corporate Affairs Commission',
                ],
                [
                    'title' => 'Apprenticeship Completion (Freedom)',
                    'issuer' => 'Master craftsman',
                ],
                [
                    'title' => 'NATA Membership',
                    'issuer' => 'Nigeria Automobile Technicians Association',
                ],
                [
                    'title' => 'NECA Membership',
                    'issuer' => 'Nigeria Electrical Contractors Association',
                ],
                [
                    'title' => 'Trade Union / Association Membership',
                    'issuer' => '',
                ],
            ],
        ],
    ],
];
