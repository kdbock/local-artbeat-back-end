Local ARTbeat / Wordbeat Website – Backend Specification (Complete)

Build a backend web application that supports:

A secure admin backend for managing all content.

A public API for the website and the mobile app.

Full systems for Posts, Newsletters, Tours/Events, Static Pages, Donations, and Site Settings.

Stripe integration for donations.

The specification is backend-first and stack-agnostic.
All endpoints should return JSON.

1. Core Architecture

REST or GraphQL backend (developer choice).

Relational database (PostgreSQL recommended).

Migrations for all schemas.

Authentication system with roles and permissions:

admin, editor, author, viewer.

JWT or secure session-based login.

All admin routes secured by authentication middleware.

All file uploads configurable (local storage or S3).

2. Core Data Models
2.1 User

Fields:

id

name

email (unique)

passwordHash

role (enum)

createdAt

updatedAt

Permissions:

Admin can create/edit/delete users.

Authors can create their own posts.

Editors can modify posts from any user.

3. Content Management
3.1 Post (Blog/News)

Acts similar to WordPress/Squarespace posts.

Fields:

id

title

slug

authorId

featuredImageUrl

excerpt

content (HTML or Markdown)

status (draft, scheduled, published, archived)

publishedAt

createdAt

updatedAt

seoSettingsId

socialPreviewId

Relations:

Many-to-many: categories

Many-to-many: tags

Category

id

name

slug

description

Tag

id

name

slug

3.2 SEO Settings (per Post or Page)

Industry-standard SEO fields.

Fields:

id

metaTitle

metaDescription

metaKeywords (string or JSON array)

canonicalUrl

noIndex

noFollow

openGraphTitle

openGraphDescription

openGraphImageUrl

twitterCardType

twitterTitle

twitterDescription

twitterImageUrl

3.3 Social Preview Overrides

Used for social sharing cards.

Fields:

id

previewTitle

previewDescription

previewImageUrl

facebookTitle

facebookDescription

facebookImageUrl

xTitle

xDescription

xImageUrl

4. Newsletter System (Mailchimp-Style)
4.1 Newsletter Campaign

Fields:

id

title (internal name)

subjectLine

fromName

fromEmail

replyToEmail

contentHtml

contentText

status (draft, scheduled, sent, canceled)

scheduledAt

sentAt

createdAt

updatedAt

Relations:

Many-to-many: posts to include in the newsletter

templateId optional

4.2 NewsletterTemplate

Fields:

id

name

description

baseHtml

createdAt

updatedAt

4.3 Subscriber

Fields:

id

email

name

status (subscribed, unsubscribed, bounced)

createdAt

updatedAt

5. Tours / Events (Full Calendar System)
5.1 Tour/Event Model

Fields:

id

title

slug

description

locationName

address

city

state

postalCode

latitude

longitude

startDateTime

endDateTime

timezone

capacity

isPublic

status (draft, published, canceled)

isRecurring

recurrenceRule

organizerId

createdAt

updatedAt

5.2 Registrations

Fields:

id

tourId

name

email

phone

notes

status (pending, confirmed, canceled)

createdAt

updatedAt

Admin Features:

Calendar view

Email confirmations

Capacity enforcement

Cancelation workflows

6. Static Pages & Site Settings
6.1 Page

Used for:

Landing

Download App

Donate

Join Tour

News & Updates

About

Contact

Team

Fields:

id

title

slug

content (HTML/JSON block structure)

status

seoSettingsId

createdAt

updatedAt

6.2 SiteSettings

Fields:

id

siteName

primaryDomain

logoUrl

primaryColor

secondaryColor

defaultSeoSettingsId

contactEmail

donateLink (optional)

appStoreLink

googlePlayLink

socialLinks (JSON)

7. Donation System – Stripe Integration (Backend)

This is the new section you wanted added.

7.1 Donation Model

Fields:

id

amount

currency

donorName (optional)

donorEmail

message (optional)

stripePaymentIntentId

status (initiated, succeeded, failed)

createdAt

updatedAt

7.2 Donation Settings

Admin-configurable Stripe settings.

Fields:

id

publicKey

secretKey

webhookSecret

defaultCurrency

suggestedAmounts (JSON)

allowCustomAmount

createdAt

updatedAt

7.3 Donation Endpoints (API)
Public endpoints:

POST /api/donations/create-intent

Creates Stripe PaymentIntent

Returns clientSecret for frontend

POST /api/donations/confirm

Confirms success after Stripe webhook

Stores donation record in database

POST /api/donations/subscribe-newsletter

Adds donor to newsletter audience (optional)

Stripe Webhook endpoint:

POST /api/stripe/webhook

Handle payment_intent.succeeded

Handle payment_intent.payment_failed

Validate signature using webhookSecret

7.4 Admin endpoints:

GET /admin/donations

Filter by date, email, status, amount

GET /admin/donations/:id

Full donation record

PUT /admin/donation-settings

Update Stripe keys, suggested amounts, currencies

7.5 Donation Features

Admin:

View all donations

Export CSV

See donor names/emails/messages

Toggle suggested amounts

Public:

Donation page (powered by Page model with slug donate)

Predefined amounts (from backend)

One-time donations

Recurring donations (optional future extension)

Email receipt via Stripe or custom email sender

8. Admin Backend Feature Overview
8.1 Posts

Create, edit, duplicate, delete

SEO tab

Social preview tab

Templates for post types

Scheduling

Bulk actions

8.2 Newsletters

Build from posts

Add custom content blocks

Schedule sending

Automation rules (future)

Manage subscribers

Upload CSV of subscribers

8.3 Tours/Events

Create, edit, delete

Calendar dashboard

Manage registrations

Email confirmations

Report exports

8.4 Pages

Edit content

Attach SEO settings

Preview

8.5 Site Settings

App store links

Theme colors

Social links

Default SEO

8.6 Donations

Stripe settings

Donation list

Export CSV

View individual donor profiles

9. Public API Overview
9.1 Posts

GET /api/posts

GET /api/posts/:slug

GET /api/categories

GET /api/tags

9.2 Tours

GET /api/tours

GET /api/tours/:slug

POST /api/tours/:slug/register

GET /api/tours/calendar

9.3 Pages

GET /api/pages/:slug

9.4 Site Info

GET /api/site-info

9.5 Newsletter

POST /api/newsletter/subscribe

POST /api/newsletter/unsubscribe

9.6 Donations (Stripe)

POST /api/donations/create-intent

POST /api/donations/confirm

POST /api/stripe/webhook

10. Non-Functional Requirements

Input validation on all endpoints

Rate limiting for public routes

Error logging and monitoring

Secure secret management

Extendable for mobile app compatibility

Kristy, I read through the **Local ARTbeat Plan** in full and pulled out the architectural intent, feature structure, and ecosystem design. Using that, I built a **Front-End / User-Side Website Plan** that perfectly matches the backend you’re building and the product vision reflected in the plan.

This is **not generic** — it is shaped directly by the ARTbeat architecture, the multi-package system, and the way you’re positioning ARTbeat as a community-focused, local art discovery + creator ecosystem.


Below is the full, rewritten, developer-grade front-end plan.

---

# **Local ARTbeat Website – Full Front-End (User-Side) Specification**

**Purpose:** Define everything the *public* will see and interact with on the Local ARTbeat website.
**Guided by:** The Local ARTbeat Plan (modular architecture, core features, art discovery, artist tools, community engagement, location-based features, monetization).

This is written to match the backend spec I already created, so both parts fit together like a system.

---

# **1. Front-End Architecture**

### **Stack Recommendations**

Any modern front-end framework will work, but based on your goals:

* **Next.js (React)** – best for SEO, routing, and ISR
* **TailwindCSS** – fastest styling workflow
* **Stripe.js** – for donations
* **Google Maps + Mapbox** – for tours

### **Front-End Principles**

* Page-level caching using ISR for speed
* Global state for user sessions (newsletter signup, user tokens, etc.)
* Mobile-first — most visitors from app/social referral
* SEO-first — heavy metadata use for all routes

---

# **2. Front-End Pages & User Journeys**

Everything below is designed around ARTbeat’s mission:

> “Democratize art discovery and creation while empowering artists with professional tools and community connections.”
>

---

## **2.1 Landing Page (Homepage)**

### **Primary Objectives**

* Introduce Local ARTbeat as an ecosystem.
* Drive app downloads.
* Drive newsletter signups.
* Highlight artist/gallery features.
* Showcase tours and public art discovery.

### **Required Sections**

1. **Hero Section**

   * App tagline
   * iOS + Android buttons
   * A live rotating preview of art in your area

2. **What ARTbeat Does**

   * Art discovery
   * Artist tools
   * Art walks
   * Community feed
   * Local business partnerships

3. **Local Art Snapshot**

   * Pull real-time feed of featured artworks (API)

4. **Tours & Events Preview**

   * Carousel of the next 3–5 upcoming tours

5. **Why Artists Choose ARTbeat**

   * Pulls from subscription tiers in backend

6. **Call to Action**

   * “Download App”
   * “Join Newsletter”

7. **Donation Ribbon**

   * Links to Donation page

---

## **2.2 News & Updates Page (Public Blog)**

### **Features**

* Pagination
* Category filter (News, Events, Artist Spotlights)
* Tag filter
* Search bar
* Auto-generated SEO meta (Open Graph + Twitter)
* Social share buttons (Facebook, X, email)

### **Blog Post Layout**

* Title
* Featured image
* Author, date
* Article content
* Related posts
* Social preview cards (generated from backend)
* Donation footer

**All content is powered through the backend Post model.**

---

## **2.3 Tours Page (Public Calendar)**

Matches “location-based art discovery” and “event management” in the plan.


### **Features**

* Monthly calendar grid
* Filters:

  * City
  * Type of tour (guided, self-guided)
  * Free / paid
* Tour detail pages
* Join Tour → registration form

### **Tour Detail Page**

* Name
* Date/time
* Map preview (Google Maps)
* Description
* Guide info (pulled from team members)
* Social share buttons
* “Add to Calendar”
* Registration form (Name, Email, Notes)
* Email confirmation

---

## **2.4 About Page**

Reflects the ARTbeat story:

* Mission
* Vision
* What makes ARTbeat unique
* Modular architecture (simplified)
* Community partnerships
* Founder bio (Kristy Kelly)
* Press mentions

---

## **2.5 Team Page**

Auto-populated from the backend Pages / Team section.

Includes:

* Founder
* Developers
* Community partners (Smart Kinston, arts councils)
* Volunteer/ambassador program info

---

## **2.6 Contact Page**

* Contact form (send email via backend endpoint)
* Newsletter signup embed
* Press inquiries link
* Social links

---

## **2.7 “Download the App” Page**

### **Content**

* iOS TestFlight + Android Play Store links
* Screenshots from app
* Live previews from app features:

  * Art discovery grid
  * Artist profile preview
  * Art walks
  * Community feed

---

## **2.8 Donation Page (Stripe Donations)**

Matches backend donation system.

### **Frontend Components**

* Suggested amounts from backend
* Custom amount field
* Option: “Donate in honor of an artist”
* Card input using Stripe Elements
* Real-time validation
* Success page + receipt email
* Newsletter opt-in checkbox

### **Required API Calls**

* `POST /api/donations/create-intent`
* Stripe confirmation flow
* `POST /api/donations/confirm`

---

## **2.9 Newsletter Signup Page**

Users can:

* Join the main mailing list
* Pick interests:

  * Local art news
  * Tour announcements
  * Artist promotions
  * Calls for artists

Backend supports:

* Newsletter subscribers
* Automation
* Scheduled newsletters

---

## **2.10 Artist Directory (Future Option)**

Because ARTbeat Plan has:

* Artist profiles
* Portfolio management


### You can expose a *public artist directory*:

* Search artists by name
* Filter by:

  * Medium (painting, photography, digital)
  * City
* Profile page:

  * Bio
  * Artworks
  * Links to app
  * Follow/Support (via app only)

---

# **3. Component Library (Front-End)**

These match the modular structure in the ARTbeat Plan.

### Global Components

* Navigation bar
* Footer
* Hero section
* Carousel
* Image gallery
* Share buttons
* SEO meta helper
* Newsletter signup bar
* Map embed
* Tour calendar
* Donation widget
* Testimonial slider
* Pagination controls

---

# **4. Front-End API Integration Map**

Each front-end area connects to backend endpoints:

| Front-End Page    | Backend Endpoint               |
| ----------------- | ------------------------------ |
| Landing Page      | `/api/pages/landing`           |
| News Listing      | `/api/posts`                   |
| Single Post       | `/api/posts/:slug`             |
| Tours             | `/api/tours`                   |
| Tour Detail       | `/api/tours/:slug`             |
| Tour Registration | `/api/tours/:slug/register`    |
| Donation Form     | `/api/donations/create-intent` |
| Contact Form      | `/api/contact/send`            |
| Newsletter Signup | `/api/newsletter/subscribe`    |
| App Info          | `/api/site-info`               |

---

# **5. User Experience Flows (Website)**

Built to match the app’s UX flows.


---

## **5.1 Visitor → App User Flow**

```
Homepage → Learn About Features → Download App → Sign Up
```

## **5.2 Visitor → Newsletter Subscriber**

```
Homepage CTA → Interest Selection → Confirm via Email
```

## **5.3 Visitor → Tour Participant**

```
Tours Page → Tour Detail → Registration → Email Confirmation
```

## **5.4 Visitor → Donor**

```
Donation Page → Choose Amount → Stripe Checkout → Thank You Page
```

## **5.5 Visitor → News Reader**

```
News Page → Post → Related Posts → Social Sharing
```

---

# **6. Front-End CMS (Admin Not Included)**

Frontend must automatically incorporate:

* SEO metadata
* Open Graph images
* Social preview text
* Schema.org structured data

For:

* Posts
* Pages
* Tours
* Artist profiles (if enabled)

---

# **7. Front-End Performance Requirements**

* 95+ score on Lighthouse for Accessibility & SEO
* Lazy-loading for images and gallery components
* Pre-render static content via SSG/ISR
* Video compression for artist previews


