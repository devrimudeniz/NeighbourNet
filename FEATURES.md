# Kalkan Social - Features and Services

This document summarizes the main features currently present in the Kalkan Social codebase.

## Contents

1. Essential Local Services
2. Business and Commerce
3. Transportation
4. Discovery and Activities
5. Community
6. AI Tools
7. Support and Platform Pages
8. Social Platform Features
9. Admin and Platform Management
10. Technical Capabilities

## 1. Essential Local Services

### Local Guidebook

- Path: `/guidebook.php`
- Expert-written guides and local recommendations
- Category filtering and sorting
- Helpful voting
- Published guide workflow
- Verified expert author model

### Community Support

- Path: `/community_support`
- Community sharing board for food, pet food, books, clothing, and other essentials
- Map-based browsing
- Give-away and mutual support use case
- Active item filtering
- Quantity tracking

### Duty Pharmacy

- Path: `/duty_pharmacy`
- On-duty pharmacy listing for the Kas / Antalya area
- Map support
- Emergency context
- API-backed refresh flow

### First Aid

- Path: `/first_aid`
- Basic emergency and first aid information
- Local health-related reference content

### Weather

- Path: `/weather`
- Local weather display
- Kalkan-focused data
- External API integration

### Pati Safe

- Path: `/pati_safe.php`
- Lost and found pet listings
- Photo gallery support
- Status tracking
- Map integration
- Poster generation for social sharing
- Subscription and notification logic

### Pet Sitting and Walking

- Path: `/pet_sitting.php`
- Pet sitter listing
- Application flow for sitters
- WhatsApp contact shortcuts
- Public sitter profiles

## 2. Business and Commerce

### Business Directory

- Path: `/directory`
- Local business listing system
- Category filters
- Search and sorting
- Business detail pages
- Ratings and reviews
- WhatsApp reservation flow
- Photo gallery support
- QR menu links where available

### Marketplace

- Path: `/marketplace.php`
- Community marketplace for second-hand items and services
- Category filtering
- Listing detail pages
- Seller profile integration
- Listing create and edit flows
- Trust-score-related features in surrounding UI

### Jobs

- Path: `/jobs`
- Local tourism and service sector job board
- Public job detail pages
- Employer and applicant flows

### Properties

- Path: `/properties`
- Property listing section for rentals and sales
- Filters such as bedrooms, pool type, and sea view
- Business users can manage multiple listings

### Utilities Status

- Path: `/status`
- Community-reported water and electricity status
- Area-based reporting
- Recent outage-style reports

## 3. Transportation

### Transportation Hub

- Path: `/transportation`
- Bus and route information
- Next bus calculation
- Taxi shortcuts
- Ride-sharing entry points

### Ride Sharing

- Path: `/rides`
- Shared ride listings
- Route, date, and seat information
- Contact actions

### Boat Trips

- Path: `/boat_trips.php`
- Public boat trip listings
- Filtering by category and price
- Captain and admin publishing flow
- Approval workflow
- Reviews and ratings

### Flights

- Path: `/flights`
- Placeholder or incomplete feature area

## 4. Discovery and Activities

### Trail Mate

- Path: `/trail_mate`
- Lycian Way route support
- GPX-based hiking route flows
- Planned walk posts
- Date-based planning support

### Happy Hour and Nightlife

- Path: `/happy_hour`
- Nightlife promotions and event visibility
- Venue-led promotional content

### Time Travel

- Path: `/time_travel`
- Historical Kalkan content
- Old versus new visual comparisons
- Local history storytelling

### What To Do Guide

- Path: `/what_to_do.php`
- Activity guide across Kalkan, Kas, and Fethiye
- Multi-location browsing
- Travel-style recommendation layout

### Events

- Path: `/events`
- Public events calendar
- Filtering and grouping
- Venue-linked events
- Approval workflow
- Cached output for performance

### Photo Contest

- Path: `/photo_contest.php`
- Community photo competition
- Weekly winners
- Gallery presentation
- Voting logic

## 5. Community

### Lingo Cards

- Path: `/lingo`
- Practical Turkish language support for everyday use

### Members

- Path: `/members`
- Member discovery
- Badge display
- Search support

### Groups

- Path: `/groups`
- Interest-based groups
- Join and leave flows
- Group posting
- Visibility controls
- Public group content can appear in the main feed

### News

- Path: `/news`
- Local news section
- Categorized regional content

## 6. AI Tools

### Paperwork Helper

- Path: `/paperwork.php`
- Turkish paperwork and document analysis
- Image upload workflow
- Gemini-backed summaries

### Grocery Scout

- Path: `/grocery.php`
- Product matching for expats
- Local equivalent suggestions
- AI-assisted comparisons

### Menu Decoder

- Path: `/menu_decoder.php`
- Menu translation support
- Food identification from images
- AI-assisted interpretation

### Pharmacy AI

- Path: `/pharmacy_ai.php`
- Medicine box identification
- Turkish medicine guidance

### Culture Lens

- Path: `/culture_lens.php`
- Historical and cultural explanation tool
- Local heritage context

## 7. Support and Platform Pages

### Contact

- Path: `/contact.php`
- Contact form
- Email sending integration

### FAQ

- Path: `/faq.php`
- Frequently asked questions
- Mission and trust pages
- Links to legal pages

### Changelog

- Path: `/changelog.php`
- Public change history
- Feature updates

### Expert Application

- Path: `/expert_application.php`
- Application flow for local experts
- Guide-writing related access model

## 8. Social Platform Features

### Feed

- Path: `/feed`
- Combined feed for wall and group content
- Multiple reactions
- Comments and mentions
- Reposts
- Save and bookmark flow
- Hashtag support
- Turkish and English translation actions
- Sorting modes such as latest and trending

### Post Detail

- Path: `/post_detail.php`
- Full post view
- Media browsing
- Comment thread display
- Reaction visibility

### Profile

- Path: `/profile.php`
- Cover and avatar handling
- About, posts, events, and friend-style sections
- Expert and AMA-related profile areas
- Mute, block, and friendship controls
- Settings modal

### Messages

- Path: `/messages`
- Direct messaging UI
- Typing indicators
- Read-state support
- Reactions on messages

### Notifications

- Path: `/notifications`
- Likes, comments, friend requests, and group notifications
- Grouped timeline UI

### Business Panel

- Path: `/business_panel.php`
- QR menu tools
- Subdomain request flow
- Business settings and social links
- Analytics-related views
- Business badge and role-oriented tools

### QR Menu System

- Business subdomain support
- Multilingual menu support
- Menu categories and products
- Social and location links
- Review prompt links

## 9. Admin and Platform Management

### Admin Dashboard

- Path: `/admin`
- User, post, event, and approval monitoring
- Overview cards and activity summaries
- Moderation entry points

### Site Settings

- Path: `/admin/site_settings.php`
- Site name management
- Short name management
- Turkish and English tagline management
- Support email and contact phone
- Application URL
- Central brand and configuration layer used by shared UI pieces

### Approval and Moderation Tools

- Verification requests
- Reports
- Property approvals
- Event approvals
- Marketplace approvals
- Boat trip approvals
- Group approvals
- Badge and user management utilities

## 10. Technical Capabilities

- Turkish and English language support
- Light and dark theme support
- Mobile-first responsive layout
- Web push support with VAPID-based configuration
- AI integrations through Gemini-powered endpoints
- Cache-backed service endpoints
- CSRF protection and PDO prepared statements
- Environment-based configuration via `.env`
- Dynamic manifest generation via `manifest.php`

Last updated: March 2026
