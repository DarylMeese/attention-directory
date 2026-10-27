# JetEngine Integration Guide

The Attention Directory plugin provides same-origin REST API endpoints optimized for **JetEngine (Crocoblock)**. This allows you to build custom listings and search filters using JetEngine's UI while keeping your API secrets secure.

## 1. Setup API Connection

1. Go to **JetEngine > Extensions** and ensure **REST API Listings** is enabled.
2. Go to **JetEngine > REST API Directives**.
3. Add New Endpoint:
   - **Name**: `Attention Listing`
   - **API Endpoint URL**: `https://your-site.com/wp-json/attention/v1/listing`
   - **Authorization**: None (WordPress handles this server-side via our proxy).
   - **Items Path**: Leave empty or use `items` (depending on our normalized shape).

## 2. Setup Query Builder

1. Go to **JetEngine > Query Builder**.
2. Add New:
   - **Query Type**: `REST API Query`.
   - **Endpoint**: Choose `Attention Listing`.
   - **Custom Body/Query**: Map your JetEngine filters (Facets) to the Attention API query params (e.g., `search`, `category`).

## 3. Create Listing Template

1. Go to **JetEngine > Listings**.
2. Add New:
   - **Listing Source**: `REST API Endpoint`.
   - **REST API Endpoint**: `Attention Listing`.
3. Design your card using Gutenberg or Elementor. Use **Dynamic Field** widgets to map to Attention fields:
   - `displayName`
   - `tagline`
   - `coverImageUrl` (use as callback for images)

## 4. Render Grid

1. Use the **Listing Grid** widget/block.
2. Select your `Attention Listing` template.
3. Enable **Lazy Load** if you have many organizations.
