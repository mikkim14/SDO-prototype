# Student Personal Dashboard

A prototype web application for students at Batangas State University-Lipa Campus to track their personal food consumption and transportation activities.

## Features

### 🍎 Food Consumption Tracking
- Manual entry of food items with quantity and calories
- Categorization by meal type (Breakfast, Lunch, Dinner, Snack)
- Daily and weekly summaries
- Data export to CSV

### 🚗 Transportation Tracking
- Log transportation modes around campus
- Calculate CO₂ emissions based on mode and distance
- Track duration and purpose of trips
- Data export to CSV

### 📊 Dashboard Overview
- Daily KPI cards showing calories, weight, distance, time, and emissions
- Weekly summary statistics
- Interactive charts for food intake trends and transport mode usage
- Date-based filtering

## File Structure

```
student-dashboard/
├── index.html          # Main dashboard page
├── food.html           # Food tracking page
├── transport.html      # Transport tracking page
├── styles.css          # CSS styling
├── data.js             # Data structures and functions
├── dashboard.js        # Dashboard page JavaScript
├── food.js             # Food page JavaScript
├── transport.js        # Transport page JavaScript
└── README.md           # This file
```

## How to Use

1. **Dashboard**: View daily summaries and weekly trends
2. **Food Tracking**: Add food entries with date, meal type, item, quantity, and calories
3. **Transport Tracking**: Log transportation with mode, distance, duration, and purpose

### Navigation
Use the sidebar menu to navigate between pages. The menu can be toggled on mobile devices.

### Data Storage
All data is stored locally in your browser's localStorage, so your entries persist between sessions.

### Export Data
Export your personal data as CSV files for backup or analysis.

## Technical Details

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Charts**: Chart.js library
- **Icons**: Font Awesome
- **Storage**: Browser localStorage
- **Responsive**: Mobile-friendly design

## Emission Factors

Transportation CO₂ emissions are calculated using these factors (kg CO₂ per km):
- Walking: 0
- Biking: 0
- Campus Shuttle: 0.05
- Bus: 0.08
- Car: 0.15
- Motorcycle: 0.10
- Other: 0.12

## Browser Compatibility

Works in all modern browsers that support:
- ES6+ JavaScript
- localStorage
- CSS Grid and Flexbox
- HTML5 Canvas (for charts)

## Development

To run locally:
1. Open any of the HTML files in a web browser
2. No server required - runs entirely in the browser

## License

This is a prototype application for educational purposes.
