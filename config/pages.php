<?php

/*
|--------------------------------------------------------------------------
| Service & Online Class page content
|--------------------------------------------------------------------------
|
| Each entry is rendered by a single Blade template. The array key is the
| URL slug, kept identical to the legacy site so existing links and search
| rankings survive the rebuild.
|
| Shape:
|   type     => 'service' | 'class'  (drives the nav group it appears under)
|   title    => page hero / breadcrumb title
|   heading  => main content heading
|   intro    => opening paragraph
|   cta      => call-to-action button label
|   image    => hero image path relative to public/
|   sections => list of { heading, intro?, items: [{ label, text }] }
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Services
    |--------------------------------------------------------------------------
    */

    'event-planning' => [
        'type' => 'service',
        'nav' => 'Event Planning & Management',
        'title' => 'Event Planning Services',
        'heading' => 'Professional Event Planning Services',
        'cta' => 'Get Service',
        'image' => 'images/eventplanning.jpg',
        'intro' => 'At Yeah Kenyan Events Limited, we specialize in turning your event ideas into unforgettable experiences. Whether you\'re planning a wedding, corporate event, conference, or private party, our team is dedicated to handling every detail. From venue selection to decorations, catering, entertainment, and logistics, we ensure everything runs smoothly, leaving you free to enjoy the event. We offer custom solutions tailored to your specific needs, style, and budget, ensuring your event is a resounding success.',
        'sections' => [
            [
                'heading' => 'Why Choose Our Event Planning Services?',
                'items' => [
                    ['label' => 'Experienced Event Planners', 'text' => 'Our team of skilled planners has years of experience in organizing a wide range of events. We understand what it takes to create memorable experiences for any occasion.'],
                    ['label' => 'Customized Event Design', 'text' => 'Each event is unique, and we tailor every aspect—from themes and decorations to entertainment and food—to your vision and preferences.'],
                    ['label' => 'Comprehensive Event Management', 'text' => 'From conceptualizing your event to its seamless execution, we offer full-service event management, ensuring everything is handled with precision and attention to detail.'],
                    ['label' => 'Professional Vendors and Partners', 'text' => 'We work with trusted vendors for catering, sound, lighting, and more, ensuring top-quality service and products for your event.'],
                    ['label' => 'Stress-Free Planning', 'text' => 'We handle the logistics so you can focus on enjoying your event. You can trust us to oversee all details, big and small, to ensure a stress-free experience.'],
                    ['label' => 'Affordable Packages', 'text' => 'We provide a range of pricing options to suit your budget without compromising on quality. You\'ll get the best value for your event investment.'],
                ],
            ],
            [
                'heading' => 'Our Additional Services',
                'intro' => 'In addition to full event planning, we also offer a range of services to enhance your event:',
                'items' => [
                    ['label' => 'Venue Selection', 'text' => 'Let us help you find the perfect venue that fits your style, guest list, and event theme. We have extensive knowledge of the best event spaces in the area.'],
                    ['label' => 'Event Entertainment', 'text' => 'From live music and DJs to interactive experiences, we can provide entertainment that will keep your guests engaged and entertained throughout the event.'],
                    ['label' => 'Event Rentals', 'text' => 'We offer high-quality rentals for all your event needs, including tables, chairs, lighting, sound systems, and more.'],
                ],
            ],
        ],
    ],

    'videography-and-photography' => [
        'type' => 'service',
        'nav' => 'Videography & Photography',
        'title' => 'Videography and Photography Services',
        'heading' => 'Professional Videography and Photography Services',
        'cta' => 'Get Service',
        'image' => 'images/drone.jpg',
        'intro' => 'We offer professional videography and photography services, capturing high-quality visuals for events, projects, and special moments. Whether it\'s for weddings, corporate events, promotional videos, or personal photoshoots, we provide tailored services to meet your needs. Our team ensures every shot is perfectly framed, and every video is expertly edited, giving you memorable and stunning results. From planning the shoot to post-production, we focus on delivering content that truly represents your vision.',
        'sections' => [
            [
                'heading' => 'Types of Videography & Photography Services',
                'items' => [
                    ['label' => 'Weddings', 'text' => 'Capture the beauty and emotion of your big day with our wedding photography and videography services, providing you with memories to cherish forever.'],
                    ['label' => 'Corporate Events', 'text' => 'We specialize in covering corporate events, conferences, and seminars, delivering professional content that can be used for marketing, training, or internal documentation.'],
                    ['label' => 'Promotional Videos', 'text' => 'Our team can create engaging promotional videos for your business, showcasing products, services, or brand identity in a creative and impactful way.'],
                    ['label' => 'Personal Photoshoots', 'text' => 'Whether for family portraits, maternity photos, or personal branding, our personalized photoshoot services capture your best moments in stunning detail.'],
                    ['label' => 'Real Estate Photography', 'text' => 'We offer professional real estate photography, ensuring your properties are showcased in the best light, whether for online listings or promotional materials.'],
                ],
            ],
            [
                'heading' => 'Why Choose Our Services?',
                'items' => [
                    ['label' => 'Expert Team', 'text' => 'Our team consists of highly skilled photographers and videographers who are passionate about capturing beautiful moments.'],
                    ['label' => 'High-Quality Equipment', 'text' => 'We use top-of-the-line cameras, lenses, and editing software to ensure that your photos and videos are of the highest quality.'],
                    ['label' => 'Creative Vision', 'text' => 'We work with you to understand your vision, ensuring that we capture your moments in the style and mood that fits your event perfectly.'],
                    ['label' => 'Personalized Service', 'text' => 'We tailor our services to fit your unique needs, making sure you get the most out of your investment in photography and videography.'],
                    ['label' => 'On-Time Delivery', 'text' => 'We guarantee timely delivery of your photos and videos, ensuring that you receive your content when you need it.'],
                ],
            ],
            [
                'heading' => 'Our Work Process',
                'intro' => 'We follow a streamlined process to ensure the best possible outcome for your videography and photography needs:',
                'items' => [
                    ['label' => 'Initial Consultation', 'text' => 'We begin by discussing your requirements, event details, and the overall vision to tailor our services accordingly.'],
                    ['label' => 'Planning the Shoot', 'text' => 'We work closely with you to plan the details, such as location, timing, and any specific shots you want to capture.'],
                    ['label' => 'Shooting', 'text' => 'Our team arrives on time, equipped with the best gear, to capture the perfect moments. We ensure everything runs smoothly on the day of the event.'],
                    ['label' => 'Post-Production', 'text' => 'After the shoot, we edit your photos and videos to enhance the quality, ensuring that the final product meets your expectations.'],
                    ['label' => 'Delivery', 'text' => 'We deliver the final product in your preferred format, whether digital, print, or online gallery, and ensure you\'re happy with the results.'],
                ],
            ],
        ],
    ],

    'live-streaming' => [
        'type' => 'service',
        'nav' => 'Live Streaming',
        'title' => 'Live Streaming Services',
        'heading' => 'Professional Live Streaming Services',
        'cta' => 'Get Service',
        'image' => 'images/livestream.jpg',
        'intro' => 'At Yeah Kenyan Events Limited, we offer top-tier live streaming services for events of all types. Whether it\'s a corporate meeting, wedding, concert, or conference, we ensure that your event reaches your audience in real-time with high-quality streaming. Our experienced team uses advanced equipment to provide seamless streaming experiences across multiple platforms like YouTube, Facebook, Zoom, and more. We handle all the technical aspects, from camera setup to multi-camera productions, so you can focus on delivering your message and engaging your viewers.',
        'sections' => [
            [
                'heading' => 'Why Choose Our Live Streaming Services?',
                'items' => [
                    ['label' => 'High-Quality Streaming', 'text' => 'We use the latest streaming equipment and technology to ensure your event is broadcasted in the highest quality possible, whether in HD or 4K resolution.'],
                    ['label' => 'Multi-Platform Streaming', 'text' => 'We can stream your event on multiple platforms simultaneously, including YouTube, Facebook Live, Vimeo, and custom websites, giving you the flexibility to reach a wider audience.'],
                    ['label' => 'Professional Team', 'text' => 'Our team of technicians and producers have years of experience in live streaming and event production, ensuring smooth and professional broadcasts every time.'],
                    ['label' => 'Multi-Camera Production', 'text' => 'For a dynamic streaming experience, we offer multi-camera setups, allowing us to capture various angles and provide professional-quality coverage of your event.'],
                    ['label' => 'Interactive Features', 'text' => 'We can integrate live chats, Q&A sessions, polls, and other interactive features to engage your online audience in real-time.'],
                    ['label' => 'Reliable Support', 'text' => 'Our team provides continuous support before, during, and after your event, ensuring everything runs smoothly with no interruptions.'],
                ],
            ],
            [
                'heading' => 'Our Additional Services',
                'intro' => 'In addition to live streaming, we also offer a range of related services to enhance your event experience:',
                'items' => [
                    ['label' => 'Event Recording', 'text' => 'We can record your event and provide you with the footage for future use, including editing and post-production services if needed.'],
                    ['label' => 'Virtual Event Hosting', 'text' => 'If you\'re hosting a virtual event or conference, we can assist with creating a custom virtual environment, integrating presentations, and ensuring seamless virtual interaction.'],
                    ['label' => 'Live Streaming for Hybrid Events', 'text' => 'For events with both in-person and online audiences, we can provide hybrid streaming solutions that ensure everyone feels included, regardless of where they are.'],
                    ['label' => 'Event Technical Support', 'text' => 'We offer full technical support for events that need a comprehensive AV setup, from sound systems to lighting, to ensure your event is professionally produced.'],
                ],
            ],
        ],
    ],

    'marketing' => [
        'type' => 'service',
        'nav' => 'Digital Marketing',
        'title' => 'Digital Marketing Services',
        'heading' => 'Drive Your Business to Success with Digital Marketing',
        'cta' => 'Start Your Digital Journey',
        'image' => 'images/branding1.jpg',
        'intro' => 'Our Digital Marketing Services are designed to help your business stand out in the ever-evolving digital world. Whether you\'re looking to increase brand awareness, drive targeted traffic, or generate high-quality leads, we offer tailored marketing strategies that are effective, scalable, and result-driven. We use a mix of SEO, social media marketing, content creation, PPC advertising, email marketing, and more to help your brand reach and engage your audience with precision. Let us take your online presence to the next level and deliver results that matter.',
        'sections' => [
            [
                'heading' => 'What We Offer with Our Digital Marketing Services',
                'items' => [
                    ['label' => 'Search Engine Optimization (SEO)', 'text' => 'We optimize your website to rank higher on search engines, driving more organic traffic and helping you stand out from the competition.'],
                    ['label' => 'Pay-Per-Click (PPC) Advertising', 'text' => 'Our PPC campaigns are meticulously crafted to drive qualified traffic to your website while maximizing your ROI. We run campaigns across Google Ads, Facebook Ads, LinkedIn Ads, and more.'],
                    ['label' => 'Social Media Marketing', 'text' => 'We build and manage your social media presence on platforms like Facebook, Instagram, LinkedIn, and Twitter, creating engaging content that drives traffic, boosts conversions, and builds brand loyalty.'],
                    ['label' => 'Content Marketing', 'text' => 'From blog posts and articles to videos and infographics, we create high-quality content that resonates with your audience and establishes your brand as a thought leader in your industry.'],
                    ['label' => 'Email Marketing', 'text' => 'Our email campaigns are personalized, designed to connect with your customers, nurture leads, and drive repeat business. We ensure every email sent serves a purpose and delivers value.'],
                    ['label' => 'Conversion Rate Optimization (CRO)', 'text' => 'We analyze your website\'s user experience, optimize key elements, and employ A/B testing to increase your website\'s conversion rates and turn more visitors into paying customers.'],
                ],
            ],
            [
                'heading' => 'Why Choose Our Digital Marketing Services?',
                'items' => [
                    ['label' => 'Results-Driven Strategies', 'text' => 'Our strategies are based on data and insights, ensuring that every campaign is measurable and optimized for maximum results.'],
                    ['label' => 'Customized Solutions', 'text' => 'We don\'t believe in one-size-fits-all approaches. Every business is unique, and we craft tailored strategies that align with your specific goals, audience, and market trends.'],
                    ['label' => 'Comprehensive Approach', 'text' => 'We take an integrated approach to digital marketing, ensuring all your channels work together to amplify your message, increase engagement, and drive conversions.'],
                    ['label' => 'Experienced Professionals', 'text' => 'Our team of digital marketing experts has years of experience in delivering successful campaigns for businesses of all sizes and industries. We stay ahead of the curve to provide you with innovative solutions.'],
                    ['label' => 'Transparent Reporting', 'text' => 'You\'ll always know how your campaigns are performing. Our detailed reports give you full visibility into the metrics that matter most to your business.'],
                    ['label' => 'Scalable Solutions', 'text' => 'As your business grows, so do your marketing needs. We offer scalable solutions that evolve with your business, ensuring that your digital marketing efforts continue to drive success at every stage.'],
                ],
            ],
            [
                'heading' => 'Tailored Digital Marketing Packages',
                'intro' => 'We understand that every business has unique needs and objectives, so we offer tailored digital marketing packages that can be customized to your goals and budget. Whether you\'re a startup looking to make your mark or an established brand wanting to scale, we have a solution for you. Our packages include:',
                'items' => [
                    ['label' => 'Basic Package', 'text' => 'Ideal for small businesses and startups looking to establish an online presence with basic SEO, social media management, and email marketing.'],
                    ['label' => 'Growth Package', 'text' => 'Perfect for growing businesses looking for more comprehensive strategies, including advanced SEO, PPC management, content marketing, and social media campaigns.'],
                    ['label' => 'Enterprise Package', 'text' => 'A full-suite digital marketing solution for larger businesses, including SEO, PPC, content marketing, email campaigns, conversion rate optimization, and a dedicated account manager to oversee all campaigns.'],
                ],
            ],
        ],
    ],

    'led-screens' => [
        'type' => 'service',
        'nav' => 'LED Screens & TV Screens',
        'title' => 'LED Screen and TV Installation Services',
        'heading' => 'Professional LED Screen and TV Installation Services',
        'cta' => 'Get Service',
        'image' => 'images/ledscreeninstall.jpg',
        'intro' => 'At Yeah Kenyan Events Limited, we provide expert LED screen and TV installation services that bring your viewing experience to the next level. Whether you\'re outfitting your home, upgrading your office, or enhancing an event space, our team ensures that your screens are installed flawlessly. We cater to all types of installations, including wall mounting, ceiling mounting, and integration into complex AV systems. Our goal is to provide you with a seamless setup that maximizes the performance of your display while blending perfectly with your environment.',
        'sections' => [
            [
                'heading' => 'Why Choose Our LED Screen and TV Installation Services?',
                'items' => [
                    ['label' => 'Experienced Technicians', 'text' => 'Our team is composed of highly trained professionals with years of experience in installing LED screens and TVs. We are experts in all aspects of TV mounting and AV system integration.'],
                    ['label' => 'Custom Solutions', 'text' => 'Every installation is tailored to meet your specific needs. We take into consideration your space, preferences, and how you plan to use the screen to ensure the best setup possible.'],
                    ['label' => 'End-to-End Service', 'text' => 'From consultation to installation and ongoing support, we provide a comprehensive service that ensures everything works flawlessly.'],
                    ['label' => 'Quality and Safety', 'text' => 'We only use high-quality, certified mounting hardware and cables to ensure your installation is secure, safe, and built to last.'],
                    ['label' => 'On-Time Installation', 'text' => 'We respect your time and work efficiently to complete the installation on time, without compromising on quality.'],
                    ['label' => 'Affordable Pricing', 'text' => 'We offer competitive pricing for our LED screen and TV installation services, ensuring you get the best value for your investment.'],
                ],
            ],
            [
                'heading' => 'Our Additional Services',
                'intro' => 'In addition to LED screen and TV installation, we also offer a variety of related services to enhance your experience:',
                'items' => [
                    ['label' => 'Smart Home Integration', 'text' => 'We can integrate your LED screen into your smart home system, allowing you to control your TV or screen with voice commands, mobile apps, or home automation devices.'],
                    ['label' => 'Sound System Setup', 'text' => 'Need a sound system to complement your TV? Our experts can install surround sound systems or soundbars for a complete entertainment experience.'],
                    ['label' => 'Event Setup', 'text' => 'We specialize in setting up LED screens for events, including corporate presentations, conferences, live events, and concerts. Let us handle the technical details so you can focus on delivering a great event.'],
                    ['label' => 'TV Repair and Maintenance', 'text' => 'We also offer repair services for your LED screens and TVs. Whether it\'s a picture issue, sound problem, or general malfunction, we\'ll get your screen back to perfect condition.'],
                ],
            ],
        ],
    ],

    'ups-services-installation' => [
        'type' => 'service',
        'nav' => 'UPS Installation & Maintenance',
        'title' => 'UPS Installation Services',
        'heading' => 'UPS Installation Services',
        'cta' => 'Get Service',
        'image' => 'images/ups-installation-services-1000x1000.webp',
        'intro' => 'We offer UPS (Uninterruptible Power Supply) installation services, providing you with reliable backup power solutions to protect your equipment from power outages and fluctuations. Whether it\'s for your home, office, or business, we handle the installation of UPS systems to ensure that critical devices such as computers, servers, and networking equipment remain powered during unexpected disruptions. Our team will assess your needs, recommend the right UPS model, and install it efficiently, ensuring everything is connected and running smoothly. With our UPS installation services, you can rest easy knowing your power supply is secure.',
        'sections' => [
            [
                'heading' => 'Why Choose Our UPS Installation Services?',
                'items' => [
                    ['label' => 'Professional Assessment', 'text' => 'Our experts evaluate your needs to recommend the best UPS model based on your power requirements.'],
                    ['label' => 'Efficient Installation', 'text' => 'We ensure that the UPS is installed quickly, with minimal disruption, and in full compliance with safety standards.'],
                    ['label' => 'Reliable Backup Power', 'text' => 'Our UPS systems provide you with reliable backup power, ensuring your devices remain operational during power outages.'],
                    ['label' => 'Ongoing Support', 'text' => 'We offer maintenance and support services to ensure your UPS continues to function optimally over time.'],
                    ['label' => 'Wide Range of UPS Systems', 'text' => 'We install a variety of UPS systems, from small units for home use to industrial-grade units for businesses.'],
                ],
            ],
            [
                'heading' => 'Types of UPS Systems We Install',
                'intro' => 'We install various types of UPS systems, each designed for specific use cases. The three most common types of UPS systems are:',
                'items' => [
                    ['label' => 'Standby UPS', 'text' => 'Ideal for homes and small businesses. It provides basic surge protection and battery backup during power outages.'],
                    ['label' => 'Line-Interactive UPS', 'text' => 'Suitable for small and medium-sized businesses. It adjusts voltage fluctuations and provides backup power, even during minor outages.'],
                    ['label' => 'Double-Conversion UPS', 'text' => 'Best for large enterprises or data centers. It provides a high level of protection by completely isolating sensitive equipment from power issues.'],
                ],
            ],
        ],
        'footnotes' => [
            'heading' => 'Additional Information',
            'body' => [
                'Whether you\'re looking to protect your home electronics or secure your business infrastructure, a UPS system is a vital investment. We ensure that your system is perfectly suited to your environment, with all the necessary configurations, wiring, and integration to ensure that your equipment is protected from power surges and outages.',
                'If you\'re uncertain about what type of UPS system is best for your needs, don\'t hesitate to reach out to us. Our experts are available to help you assess your needs and recommend the ideal solution. Additionally, we provide regular maintenance checks and battery replacements to ensure your UPS operates smoothly throughout its lifespan.',
            ],
        ],
    ],

    'stage' => [
        'type' => 'service',
        'nav' => 'Stage',
        'title' => 'Stage Services',
        'heading' => 'Professional Stage Services',
        'cta' => 'Get Service',
        'image' => 'images/stage.jpg',
        'intro' => 'At Yeah Kenyan Events Limited, we offer top-tier stage services for events of all types. Whether it\'s a corporate function, wedding, concert, or conference, we ensure that your stage setup is professional, safe, and visually stunning. Our experienced team provides customized stage designs, lighting, sound systems, and all technical aspects to make your event outstanding.',
        'sections' => [
            [
                'heading' => 'Why Choose Our Stage Services?',
                'items' => [
                    ['label' => 'Custom Stage Designs', 'text' => 'We create stages tailored to your event\'s theme and requirements, ensuring a unique and visually appealing setup.'],
                    ['label' => 'High-Quality Equipment', 'text' => 'We use the latest technology for stage lighting, sound, and effects to enhance the overall experience.'],
                    ['label' => 'Professional Setup', 'text' => 'Our team of experts ensures a secure and stable stage setup, meeting all safety standards.'],
                    ['label' => 'Comprehensive Technical Support', 'text' => 'We provide on-site technical assistance to handle all stage-related requirements smoothly.'],
                    ['label' => 'Versatility', 'text' => 'Our stage services cater to events of all sizes, from small gatherings to large-scale concerts and corporate events.'],
                ],
            ],
            [
                'heading' => 'Our Additional Services',
                'intro' => 'In addition to stage setup, we also offer a range of related services to enhance your event experience:',
                'items' => [
                    ['label' => 'Event Lighting', 'text' => 'We provide dynamic lighting solutions to enhance the mood and atmosphere of your event.'],
                    ['label' => 'Sound Systems', 'text' => 'Our high-quality audio solutions ensure crystal-clear sound for your event.'],
                    ['label' => 'Backdrop and Decor', 'text' => 'We offer customized backdrops, banners, and decorations to match your event theme.'],
                    ['label' => 'Full Event Production', 'text' => 'From planning to execution, we handle all aspects of your event\'s technical needs.'],
                ],
            ],
        ],
    ],

    'canopies' => [
        'type' => 'service',
        'nav' => 'Canopies',
        'title' => 'Canopies',
        'heading' => 'Professional Canopy Services',
        'cta' => 'Get Service',
        'image' => 'images/eventplanning.jpg',
        'intro' => 'At Yeah Kenyan Events Limited, we provide high-quality canopy solutions for various events, including weddings, corporate functions, outdoor exhibitions, and private gatherings. Our canopies are designed to offer both elegance and functionality, ensuring your event is well-covered and visually appealing.',
        'sections' => [
            [
                'heading' => 'Why Choose Our Canopy Services?',
                'items' => [
                    ['label' => 'Elegant & Durable Designs', 'text' => 'Our canopies come in various styles and are made from high-quality materials to ensure durability and aesthetics.'],
                    ['label' => 'Weather Protection', 'text' => 'We offer canopies that provide shade and shelter from rain, ensuring your event remains comfortable.'],
                    ['label' => 'Custom Sizes & Styles', 'text' => 'Whether you need a simple tent or an elaborate marquee, we have customizable options to suit your event.'],
                    ['label' => 'Professional Installation', 'text' => 'Our team ensures proper setup and dismantling, making your event planning stress-free.'],
                    ['label' => 'Affordable Packages', 'text' => 'We provide budget-friendly canopy solutions without compromising on quality and design.'],
                ],
            ],
            [
                'heading' => 'Our Additional Services',
                'intro' => 'In addition to canopy rentals, we offer a range of related services to enhance your event experience:',
                'items' => [
                    ['label' => 'Event Furniture', 'text' => 'We provide tables, chairs, and decor to complement your canopy setup.'],
                    ['label' => 'Lighting & Decoration', 'text' => 'Enhance your canopy with professional lighting, drapes, and decor for a stunning ambiance.'],
                    ['label' => 'Flooring & Carpeting', 'text' => 'We offer customized flooring solutions to match your event theme and setting.'],
                    ['label' => 'On-Site Support', 'text' => 'Our team remains available throughout your event to ensure seamless setup and maintenance.'],
                ],
            ],
        ],
    ],

    'camera-lighting-stage-lighting' => [
        'type' => 'service',
        'nav' => 'Camera Lighting & Stage Lighting',
        'title' => 'Camera Lighting and Stage Lighting',
        'heading' => 'Professional Lighting Services',
        'cta' => 'Get Service',
        'image' => 'images/stage.jpg',
        'intro' => 'At Yeah Kenyan Events Limited, we specialize in providing high-quality camera and stage lighting solutions for all types of events. Whether it\'s a live concert, corporate event, wedding, or film production, our advanced lighting equipment enhances visibility, mood, and the overall experience.',
        'sections' => [
            [
                'heading' => 'Why Choose Our Lighting Services?',
                'items' => [
                    ['label' => 'Professional-Grade Lighting', 'text' => 'We use high-end lighting equipment to create the perfect ambiance for any event.'],
                    ['label' => 'Stage Lighting', 'text' => 'Our dynamic stage lighting solutions enhance performances with various effects, colors, and intensities.'],
                    ['label' => 'Camera-Friendly Setup', 'text' => 'We optimize lighting for video and photography to ensure clear, high-quality visuals.'],
                    ['label' => 'Customizable Solutions', 'text' => 'Whether it\'s soft ambient lighting or high-intensity stage effects, we tailor our services to fit your event\'s needs.'],
                    ['label' => 'Expert Technicians', 'text' => 'Our lighting professionals ensure seamless setup and operation for a flawless experience.'],
                ],
            ],
            [
                'heading' => 'Our Additional Services',
                'intro' => 'In addition to lighting solutions, we offer related services to enhance your event setup:',
                'items' => [
                    ['label' => 'Stage Design', 'text' => 'We provide complete stage setup, including trusses and backdrops.'],
                    ['label' => 'Sound & Audio Integration', 'text' => 'Our lighting solutions work seamlessly with professional sound systems.'],
                    ['label' => 'Event Video Production', 'text' => 'We collaborate with videographers to ensure the best lighting for film and live streaming.'],
                    ['label' => 'On-Site Technical Support', 'text' => 'Our team remains on-site to manage lighting throughout your event.'],
                ],
            ],
        ],
    ],

    'high-quality-and-line-array-sound-system' => [
        'type' => 'service',
        'nav' => 'High Quality & Line Array Sound System',
        'title' => 'High-Quality and Line Array Sound Systems',
        'heading' => 'Professional Sound System Services',
        'cta' => 'Get Service',
        'image' => 'images/soundsystem.jpg',
        'intro' => 'At Yeah Kenyan Events Limited, we provide top-tier high-quality and line array sound systems for all types of events. Whether it\'s a concert, wedding, corporate event, or private function, our professional sound solutions ensure crystal-clear audio and an immersive sound experience.',
        'sections' => [
            [
                'heading' => 'Why Choose Our Sound System Services?',
                'items' => [
                    ['label' => 'Premium Sound Quality', 'text' => 'Our systems deliver clear, distortion-free sound, perfect for large and small gatherings.'],
                    ['label' => 'Line Array Technology', 'text' => 'We use line array systems for even sound distribution, ensuring every guest enjoys a top-quality audio experience.'],
                    ['label' => 'Custom Setup', 'text' => 'Whether you need a full concert rig or a simple PA system, we provide tailored solutions.'],
                    ['label' => 'Professional Engineers', 'text' => 'Our team of sound experts ensures proper setup, calibration, and live monitoring for optimal performance.'],
                    ['label' => 'Reliable Equipment', 'text' => 'We use industry-leading brands for consistent and reliable sound performance.'],
                ],
            ],
            [
                'heading' => 'Our Additional Services',
                'intro' => 'In addition to sound system rentals, we offer related services to enhance your event experience:',
                'items' => [
                    ['label' => 'Stage & Lighting', 'text' => 'Complete your event setup with professional staging and lighting solutions.'],
                    ['label' => 'Microphone & Wireless Systems', 'text' => 'We provide high-quality wired and wireless microphones for seamless communication.'],
                    ['label' => 'Event Audio Recording', 'text' => 'Capture high-quality audio recordings for post-event use.'],
                    ['label' => 'On-Site Technical Support', 'text' => 'Our team remains on-site to ensure flawless audio throughout your event.'],
                ],
            ],
        ],
    ],

    'building-booth-stands' => [
        'type' => 'service',
        'nav' => 'Building Booth Stands',
        'title' => 'Building Booth Stands',
        'heading' => 'Professional Booth Stand Services',
        'cta' => 'Get Service',
        'image' => 'images/branding1.jpg',
        'intro' => 'At Yeah Kenyan Events Limited, we specialize in designing and constructing high-quality booth stands for exhibitions, trade shows, and corporate events. Our expert team creates custom booths that enhance brand visibility and engagement, ensuring that your business stands out in any event setting.',
        'sections' => [
            [
                'heading' => 'Why Choose Our Booth Stand Services?',
                'items' => [
                    ['label' => 'Custom Designs', 'text' => 'We create unique and visually appealing booth stands tailored to your brand and event requirements.'],
                    ['label' => 'High-Quality Materials', 'text' => 'Our booth stands are built using durable materials to ensure a premium and long-lasting setup.'],
                    ['label' => 'Professional Installation', 'text' => 'Our experienced team handles the entire setup process, ensuring a flawless and hassle-free experience.'],
                    ['label' => 'Innovative Branding', 'text' => 'We integrate creative branding elements, including digital screens, banners, and lighting, to maximize your brand\'s impact.'],
                    ['label' => 'Flexible Solutions', 'text' => 'Whether you need a small exhibition stand or a large-scale trade show booth, we provide adaptable solutions to suit your needs.'],
                ],
            ],
            [
                'heading' => 'Our Additional Services',
                'intro' => 'In addition to booth stand construction, we offer a range of related services to enhance your exhibition experience:',
                'items' => [
                    ['label' => 'Graphic Design & Printing', 'text' => 'We provide high-quality banners, posters, and signage to complement your booth setup.'],
                    ['label' => 'Lighting & AV Integration', 'text' => 'Enhance your booth with professional lighting and audiovisual solutions for an engaging presentation.'],
                    ['label' => 'On-Site Support', 'text' => 'Our team provides full support during the event to ensure your booth remains functional and attractive.'],
                    ['label' => 'Event Logistics & Management', 'text' => 'From transportation to installation and dismantling, we handle all logistical aspects of your booth setup.'],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Online Classes
    |--------------------------------------------------------------------------
    */

    'camera-work' => [
        'type' => 'class',
        'nav' => 'Camera Work',
        'title' => 'Camera Work Classes',
        'heading' => 'Learn the Art of Camera Work',
        'cta' => 'Enroll Now',
        'image' => 'images/team4.jpg',
        'intro' => 'Our Camera Work Classes are designed to help you master the skills of cinematography and camera operation. Whether you\'re an aspiring filmmaker, a hobbyist, or someone looking to refine their technical skills, our comprehensive courses cover everything you need to know. From learning about different types of cameras and lenses to understanding framing, lighting, and camera movement, we provide hands-on instruction to help you capture the perfect shot every time.',
        'sections' => [
            [
                'heading' => 'What You\'ll Learn in Camera Work Classes',
                'items' => [
                    ['label' => 'Camera Basics', 'text' => 'Learn about different types of cameras, lenses, and equipment, and understand the basic components of camera systems.'],
                    ['label' => 'Framing & Composition', 'text' => 'Master the art of framing your subject and composing shots for visual impact, following the rule of thirds and other compositional techniques.'],
                    ['label' => 'Lighting Techniques', 'text' => 'Understand the importance of lighting in camera work, and learn various lighting setups like three-point lighting and natural lighting techniques.'],
                    ['label' => 'Camera Angles and Movements', 'text' => 'Discover how to use different camera angles, shots, and movements (e.g., pans, tilts, dolly shots) to tell a compelling story.'],
                    ['label' => 'Depth of Field & Focus', 'text' => 'Learn how to control depth of field and focus, and use it creatively to enhance the mood and storytelling of your shots.'],
                    ['label' => 'Shot Planning and Storyboarding', 'text' => 'Understand how to plan your shots and create storyboards, ensuring every shot serves the story you\'re telling.'],
                    ['label' => 'Camera Techniques for Different Genres', 'text' => 'Explore camera work for various genres including documentaries, music videos, commercials, and narrative films.'],
                    ['label' => 'Post-Production Workflow', 'text' => 'Understand the post-production process related to camera work, including how to work with editors and colorists to get the most out of your footage.'],
                ],
            ],
            [
                'heading' => 'Why Choose Our Camera Work Classes?',
                'items' => [
                    ['label' => 'Experienced Instructors', 'text' => 'Our instructors are seasoned professionals with years of experience in film and television production, offering real-world insights.'],
                    ['label' => 'Hands-On Learning', 'text' => 'We emphasize practical, hands-on learning. You\'ll have opportunities to operate cameras, shoot real scenes, and get direct feedback.'],
                    ['label' => 'Comprehensive Curriculum', 'text' => 'Our curriculum covers both the technical and creative aspects of camera work, ensuring a well-rounded understanding.'],
                    ['label' => 'Small Class Sizes', 'text' => 'We keep class sizes small to ensure personalized attention and a more effective learning environment.'],
                    ['label' => 'Equipment & Studio Access', 'text' => 'Students have access to top-of-the-line cameras, lenses, and studio setups, allowing them to practice and experiment with professional gear.'],
                ],
            ],
            [
                'heading' => 'Course Structure',
                'intro' => 'Our Camera Work Classes are structured to offer both theoretical knowledge and practical skills. Here\'s a breakdown of the course modules:',
                'items' => [
                    ['label' => 'Module 1: Introduction to Camera Work', 'text' => 'Understanding different types of cameras and basic operation.'],
                    ['label' => 'Module 2: Composition & Framing', 'text' => 'Techniques for visually appealing and effective shots.'],
                    ['label' => 'Module 3: Lighting & Exposure', 'text' => 'The science and art of lighting your scenes for maximum impact.'],
                    ['label' => 'Module 4: Advanced Camera Techniques', 'text' => 'Exploring complex shots, camera movements, and storytelling methods.'],
                    ['label' => 'Module 5: Working with Crews', 'text' => 'Learn how to collaborate with other crew members, such as directors and gaffers, to execute the vision.'],
                    ['label' => 'Module 6: Final Project', 'text' => 'Put your skills to the test by shooting and editing your own short film or project.'],
                ],
            ],
        ],
    ],

    'live-streaming-classes' => [
        'type' => 'class',
        'nav' => 'Live Streaming',
        'title' => 'Live Streaming Classes',
        'heading' => 'Learn the Art of Live Streaming',
        'cta' => 'Enroll Now',
        'image' => 'images/streaming1.jpg',
        'intro' => 'Our Live Streaming Classes are designed to help you master the technical and creative aspects of live broadcasting. Whether you\'re interested in streaming events, content creation for social media, or even live professional conferences, our classes will teach you the tools and techniques needed to broadcast content seamlessly. From camera setup and multi-platform streaming to audio mixing and viewer interaction, we will guide you through every step of the process to ensure that you can successfully deliver high-quality live streams.',
        'sections' => [
            [
                'heading' => 'What You\'ll Learn in Live Streaming Classes',
                'items' => [
                    ['label' => 'Understanding Streaming Platforms', 'text' => 'Learn about various streaming platforms like YouTube, Twitch, Facebook Live, and others. We\'ll cover how to set up accounts, configure streaming settings, and optimize streams for each platform.'],
                    ['label' => 'Camera Setup for Live Streaming', 'text' => 'Learn how to choose and set up cameras for live broadcasts, from webcams to professional cameras. Understand how to adjust settings for optimal quality.'],
                    ['label' => 'Audio Mixing & Sound Setup', 'text' => 'Master the art of balancing audio levels, using external microphones, and ensuring high-quality sound for your streams.'],
                    ['label' => 'Lighting Techniques', 'text' => 'Learn how to properly light your streaming setup to ensure your viewers see you clearly, especially in low-light conditions.'],
                    ['label' => 'Streaming Software', 'text' => 'Dive into the most popular streaming software like OBS Studio, Streamlabs, and vMix. Learn how to configure scenes, add graphics, and manage overlays to enhance your stream.'],
                    ['label' => 'Multi-Camera Streams', 'text' => 'Understand how to use multiple cameras for dynamic streams, including switching between different camera angles in real-time for a professional feel.'],
                    ['label' => 'Live Streaming for Events', 'text' => 'Learn how to handle live events, including sports, conferences, concerts, and more. Learn to manage the stream from start to finish, ensuring everything runs smoothly.'],
                    ['label' => 'Audience Interaction', 'text' => 'Understand the importance of engaging your audience during live broadcasts, including managing live chat, taking questions, and responding in real-time.'],
                ],
            ],
            [
                'heading' => 'Why Choose Our Live Streaming Classes?',
                'items' => [
                    ['label' => 'Expert Instructors', 'text' => 'Our instructors are experienced live streamers who have worked on high-profile broadcasts and are passionate about teaching others the art of live streaming.'],
                    ['label' => 'Hands-On Experience', 'text' => 'In our classes, you will gain practical experience by running live streams from start to finish. You\'ll set up equipment, troubleshoot issues, and manage a live broadcast in a controlled, guided environment.'],
                    ['label' => 'Up-to-Date Knowledge', 'text' => 'We teach the latest tools, software, and industry practices, ensuring that you\'re ready for the current trends in live streaming and broadcasting.'],
                    ['label' => 'Comprehensive Curriculum', 'text' => 'Our curriculum covers everything from the basics to advanced techniques, ensuring you\'re fully equipped to handle any live streaming situation.'],
                    ['label' => 'Small Class Sizes', 'text' => 'We maintain small class sizes to give each student personal attention and ensure a more interactive and effective learning environment.'],
                ],
            ],
            [
                'heading' => 'Course Structure',
                'intro' => 'Our Live Streaming Classes are designed to take you step-by-step through the process of producing professional live streams. Below is a breakdown of the course structure:',
                'items' => [
                    ['label' => 'Module 1: Introduction to Live Streaming', 'text' => 'Learn the fundamentals of live streaming, including platforms, broadcasting basics, and the necessary equipment.'],
                    ['label' => 'Module 2: Camera & Audio Setup', 'text' => 'Understand the best practices for camera setup, audio equipment, and lighting for a flawless live stream.'],
                    ['label' => 'Module 3: Streaming Software', 'text' => 'Get hands-on experience with popular live streaming software like OBS Studio and Streamlabs, and learn how to create and manage scenes, overlays, and live settings.'],
                    ['label' => 'Module 4: Multi-Camera & Switching Techniques', 'text' => 'Learn how to set up and switch between multiple camera angles to enhance your stream\'s production value.'],
                    ['label' => 'Module 5: Managing Live Events', 'text' => 'Focus on how to handle live broadcasts for events, from sports to corporate conferences, ensuring smooth operation during the stream.'],
                    ['label' => 'Module 6: Viewer Engagement & Analytics', 'text' => 'Learn how to interact with viewers in real-time and track the success of your stream using analytics and engagement metrics.'],
                    ['label' => 'Module 7: Final Project', 'text' => 'Produce and execute your live stream with the support of your instructors, demonstrating everything you\'ve learned throughout the course.'],
                ],
            ],
        ],
    ],

    'science-and-physics-classes' => [
        'type' => 'class',
        'nav' => 'Science and Physics',
        'title' => 'Science and Physics Classes',
        'heading' => 'Explore the Wonders of Science and Physics',
        'cta' => 'Enroll Now',
        'image' => 'images/team2.jpg',
        'intro' => 'Our Science and Physics Classes offer an exciting opportunity to dive deep into the world of natural sciences. Whether you\'re passionate about understanding the laws of nature, the mysteries of the universe, or simply looking to enhance your knowledge, these classes will guide you through key concepts in physics and beyond. Our expert instructors will help you master topics from classical mechanics to quantum physics, with practical applications that shape the world around us. Get ready to unlock the power of science and physics through engaging lessons, experiments, and real-world examples.',
        'sections' => [
            [
                'heading' => 'What You\'ll Learn in Science and Physics Classes',
                'items' => [
                    ['label' => 'Fundamentals of Physics', 'text' => 'Learn the basic concepts of physics, including motion, force, energy, and matter, and how they shape the physical world around us.'],
                    ['label' => 'Classical Mechanics', 'text' => 'Study the laws of motion and Newton\'s principles, exploring how objects move and interact under the influence of forces.'],
                    ['label' => 'Electromagnetism', 'text' => 'Understand the principles of electricity and magnetism, including circuits, fields, and electromagnetic waves that power modern technologies.'],
                    ['label' => 'Thermodynamics', 'text' => 'Delve into the study of heat, energy transfer, and the laws governing thermodynamic systems and processes.'],
                    ['label' => 'Quantum Mechanics', 'text' => 'Explore the fascinating world of subatomic particles and the strange behaviors governed by quantum theory.'],
                    ['label' => 'Relativity', 'text' => 'Learn about Einstein\'s theory of relativity, including concepts such as time dilation and the curvature of space-time.'],
                    ['label' => 'Atomic and Nuclear Physics', 'text' => 'Study the structure of atoms, nuclear reactions, and radioactivity, gaining an understanding of their applications in energy production and medical technologies.'],
                    ['label' => 'Astrophysics', 'text' => 'Discover the principles that govern celestial bodies and the universe, including the formation of stars, galaxies, and black holes.'],
                    ['label' => 'Modern Physics', 'text' => 'Explore recent developments in physics, such as particle physics, string theory, and the search for a unified theory of everything.'],
                ],
            ],
            [
                'heading' => 'Why Choose Our Science and Physics Classes?',
                'items' => [
                    ['label' => 'Expert Instructors', 'text' => 'Our instructors are experienced scientists and physicists who are passionate about teaching and making complex concepts understandable.'],
                    ['label' => 'Hands-On Learning', 'text' => 'We believe in learning by doing, so our classes include interactive experiments, demonstrations, and practical applications to reinforce theoretical knowledge.'],
                    ['label' => 'Comprehensive Curriculum', 'text' => 'Our curriculum covers everything from basic principles to advanced topics, ensuring students build a strong foundation and understand cutting-edge developments in physics.'],
                    ['label' => 'Small Class Sizes', 'text' => 'We maintain small class sizes to ensure personalized attention and foster meaningful student-teacher interactions.'],
                    ['label' => 'State-of-the-Art Facilities', 'text' => 'Our classrooms are equipped with modern labs and technology to facilitate interactive learning and experimentation in physics.'],
                ],
            ],
            [
                'heading' => 'Course Structure',
                'intro' => 'Our Science and Physics Classes are structured to help you progress from foundational knowledge to advanced concepts. Below is a breakdown of our course structure:',
                'items' => [
                    ['label' => 'Module 1: Introduction to Physics', 'text' => 'Learn the fundamental concepts of physics and the scientific method, building the foundation for your studies.'],
                    ['label' => 'Module 2: Mechanics', 'text' => 'Study the laws of motion, force, energy, and momentum, applying them to real-world scenarios.'],
                    ['label' => 'Module 3: Electricity and Magnetism', 'text' => 'Dive into the study of electric fields, circuits, magnetic forces, and electromagnetism.'],
                    ['label' => 'Module 4: Thermodynamics', 'text' => 'Understand the principles of energy transfer, temperature, heat, and the laws of thermodynamics.'],
                    ['label' => 'Module 5: Waves and Optics', 'text' => 'Learn about sound waves, light, and how lenses and mirrors work to manipulate light.'],
                    ['label' => 'Module 6: Quantum Mechanics', 'text' => 'Explore the world of atoms, subatomic particles, and quantum theory that defy classical physics.'],
                    ['label' => 'Module 7: Relativity and Astrophysics', 'text' => 'Learn about Einstein\'s theories of relativity, and study the behavior of the universe through astrophysics and cosmology.'],
                    ['label' => 'Module 8: Advanced Topics in Physics', 'text' => 'Delve into advanced concepts, including nuclear physics, particle physics, and string theory, with an emphasis on cutting-edge research and technologies.'],
                    ['label' => 'Module 9: Final Project', 'text' => 'Apply everything you\'ve learned in a comprehensive project, conducting experiments or research to demonstrate your understanding of physics principles.'],
                ],
            ],
        ],
    ],

    'digital-marketing-classes' => [
        'type' => 'class',
        'nav' => 'Digital Marketing',
        'title' => 'Digital Marketing Classes',
        'heading' => 'Master the Art of Digital Marketing',
        'cta' => 'Enroll Now',
        'image' => 'images/branding1.jpg',
        'intro' => 'Our Digital Marketing Classes provide an in-depth look at the ever-evolving world of online marketing. Whether you\'re a beginner or looking to enhance your skills, these classes cover essential digital marketing strategies and tools that will help you succeed in today\'s competitive market. From SEO and social media marketing to email campaigns and paid ads, our expert instructors will guide you through practical applications and case studies, ensuring you gain real-world knowledge that you can apply to any digital marketing platform.',
        'sections' => [
            [
                'heading' => 'What You\'ll Learn in Digital Marketing Classes',
                'items' => [
                    ['label' => 'Digital Marketing Fundamentals', 'text' => 'Learn the basics of digital marketing, including key concepts, strategies, and tools that make up a successful digital marketing campaign.'],
                    ['label' => 'Search Engine Optimization (SEO)', 'text' => 'Master the art of optimizing websites to rank higher on search engines, including keyword research, on-page SEO, and link building.'],
                    ['label' => 'Social Media Marketing', 'text' => 'Learn how to leverage platforms like Facebook, Instagram, Twitter, and LinkedIn to promote your brand and engage with your audience.'],
                    ['label' => 'Email Marketing', 'text' => 'Understand the strategies behind creating effective email campaigns, from list building to crafting compelling content and analyzing campaign performance.'],
                    ['label' => 'Pay-Per-Click (PPC) Advertising', 'text' => 'Dive into paid advertising models like Google Ads and Facebook Ads, and learn how to create and optimize effective ad campaigns.'],
                    ['label' => 'Content Marketing', 'text' => 'Learn how to create engaging content that attracts and retains an audience, driving traffic and conversions.'],
                    ['label' => 'Affiliate Marketing', 'text' => 'Discover how affiliate marketing works and how to build partnerships to promote products and services to a broader audience.'],
                    ['label' => 'Analytics and Data Analysis', 'text' => 'Gain insights into how to measure and analyze your marketing efforts using tools like Google Analytics, and understand key metrics that impact marketing success.'],
                    ['label' => 'Conversion Rate Optimization (CRO)', 'text' => 'Learn how to optimize landing pages and marketing funnels to maximize the conversion of visitors into customers.'],
                ],
            ],
            [
                'heading' => 'Why Choose Our Digital Marketing Classes?',
                'items' => [
                    ['label' => 'Expert Instructors', 'text' => 'Our instructors are seasoned digital marketers who bring years of industry experience and a passion for teaching the latest trends and strategies.'],
                    ['label' => 'Hands-On Learning', 'text' => 'We focus on practical, real-world learning experiences, giving you the tools to manage your own campaigns from day one.'],
                    ['label' => 'Comprehensive Curriculum', 'text' => 'Our curriculum covers all aspects of digital marketing, ensuring you have a well-rounded understanding of each component and how they work together.'],
                    ['label' => 'Small Class Sizes', 'text' => 'We maintain small class sizes to provide personalized attention and to foster meaningful interactions between students and instructors.'],
                    ['label' => 'State-of-the-Art Tools', 'text' => 'Get hands-on experience with the same marketing tools used by professionals, including Google Ads, Facebook Ads Manager, and email marketing platforms.'],
                ],
            ],
            [
                'heading' => 'Course Structure',
                'intro' => 'Our Digital Marketing Classes are designed to take you from basic to advanced digital marketing skills. Below is a breakdown of the course structure:',
                'items' => [
                    ['label' => 'Module 1: Introduction to Digital Marketing', 'text' => 'Learn the fundamentals of digital marketing and understand the key channels that drive online business growth.'],
                    ['label' => 'Module 2: Search Engine Optimization (SEO)', 'text' => 'Dive deeper into on-page and off-page SEO, keyword research, and ranking strategies.'],
                    ['label' => 'Module 3: Social Media Marketing', 'text' => 'Master the strategies for growing your brand on popular social platforms and learn how to run successful paid campaigns.'],
                    ['label' => 'Module 4: Email Marketing', 'text' => 'Discover how to build effective email marketing campaigns that engage subscribers and convert them into customers.'],
                    ['label' => 'Module 5: Paid Media Advertising', 'text' => 'Learn the ins and outs of Google Ads, Facebook Ads, and other PPC platforms to generate immediate traffic to your website.'],
                    ['label' => 'Module 6: Content Marketing', 'text' => 'Understand the power of creating valuable content that attracts and retains customers and how to integrate content with SEO strategies.'],
                    ['label' => 'Module 7: Analytics and Reporting', 'text' => 'Learn how to analyze campaign data, interpret metrics, and make data-driven decisions to improve marketing efforts.'],
                    ['label' => 'Module 8: Conversion Rate Optimization (CRO)', 'text' => 'Discover how to optimize your landing pages and marketing funnels to increase conversions and revenue.'],
                    ['label' => 'Module 9: Final Project', 'text' => 'Apply everything you\'ve learned by developing and presenting a comprehensive digital marketing campaign, showcasing your understanding of various marketing tools and strategies.'],
                ],
            ],
        ],
    ],

];
