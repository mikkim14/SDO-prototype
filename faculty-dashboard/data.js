// Student Personal Data Structures (copied for faculty)

// Food consumption data
// Format: { date, mealType, item, quantity, calories }
let foodData = [];

// Transportation data
// Format: { date, mode, distance, duration, purpose, emissions }
let transportData = [];

// Transport emission factors (kg CO2 per km)
const EMISSION_FACTORS = {
    'Walking': 0,
    'Biking': 0,
    'Bus': 0.08,
    'Car': 0.15,
    'Motorcycle': 0.10,
    'Other': 0.12
};

// Sample food data
const sampleFoodData = [
    { date: '2024-01-15', mealType: 'Breakfast', item: 'Rice', quantity: 100, calories: 130 },
    { date: '2024-01-15', mealType: 'Breakfast', item: 'Eggs', quantity: 50, calories: 70 },
    { date: '2024-01-15', mealType: 'Lunch', item: 'Chicken', quantity: 150, calories: 250 },
    { date: '2024-01-15', mealType: 'Lunch', item: 'Rice', quantity: 200, calories: 260 },
    { date: '2024-01-15', mealType: 'Snack', item: 'Apple', quantity: 150, calories: 80 },
    { date: '2024-01-16', mealType: 'Breakfast', item: 'Cereal', quantity: 50, calories: 190 },
    { date: '2024-01-16', mealType: 'Breakfast', item: 'Milk', quantity: 200, calories: 120 },
    { date: '2024-01-16', mealType: 'Lunch', item: 'Pasta', quantity: 200, calories: 350 },
    { date: '2024-01-16', mealType: 'Dinner', item: 'Fish', quantity: 120, calories: 180 },
    { date: '2024-01-16', mealType: 'Dinner', item: 'Vegetables', quantity: 100, calories: 50 }
];

// Sample transport data
const sampleTransportData = [
    { date: '2024-01-15', mode: 'Walking', distance: 1.5, duration: 20, purpose: 'To class' },
    { date: '2024-01-15', mode: 'Campus Shuttle', distance: 2.0, duration: 15, purpose: 'To cafeteria' },
    { date: '2024-01-15', mode: 'Walking', distance: 0.8, duration: 12, purpose: 'To library' },
    { date: '2024-01-16', mode: 'Biking', distance: 3.0, duration: 18, purpose: 'To campus entrance' },
    { date: '2024-01-16', mode: 'Walking', distance: 1.2, duration: 16, purpose: 'To gym' },
    { date: '2024-01-16', mode: 'Bus', distance: 5.0, duration: 25, purpose: 'Home to campus' },
    { date: '2024-01-17', mode: 'Walking', distance: 2.1, duration: 25, purpose: 'Campus tour' }
];


// Calculate emissions for transport entry (based on minutes)
const EMISSION_RATE_PER_MINUTE = {
    'Bus': 0.01,
    'Car': 0.02,
    'Motorcycle': 0.015,
    'Other': 0.012
};
function calculateEmissions(mode, duration) {
    const rate = EMISSION_RATE_PER_MINUTE[mode] || 0;
    return (rate * duration).toFixed(3);
}

// Validation functions
function validateFoodEntry(date, mealType, item, quantity, calories) {
    const errors = [];

    if (!date) {
        errors.push('Date is required');
    }
    if (!mealType) {
        errors.push('Meal type is required');
    }
    if (!item || item.trim().length === 0) {
        errors.push('Food item is required');
    }
    if (!quantity || quantity <= 0) {
        errors.push('Quantity must be greater than 0');
    }
    if (!calories || calories < 0) {
        errors.push('Calories must be 0 or greater');
    }

    return errors;
}

function validateTransportEntry(date, mode, distance, duration, purpose) {
    const errors = [];

    if (!date) {
        errors.push('Date is required');
    }
    if (!mode) {
        errors.push('Transport mode is required');
    }
    if (!distance || distance < 0) {
        errors.push('Distance must be 0 or greater');
    }
    if (!duration || duration <= 0) {
        errors.push('Duration must be greater than 0');
    }
    if (!purpose || purpose.trim().length === 0) {
        errors.push('Purpose is required');
    }

    return errors;
}

// Data processing functions
function getDailyFoodSummary(date) {
    const dayData = foodData.filter(entry => entry.date === date);
    return {
        totalCalories: dayData.reduce((sum, entry) => sum + entry.calories, 0),
        totalWeight: dayData.reduce((sum, entry) => sum + entry.quantity, 0),
        itemCount: dayData.length
    };
}

function getDailyTransportSummary(date) {
    const dayData = transportData.filter(entry => entry.date === date);
    return {
        totalDistance: dayData.reduce((sum, entry) => sum + entry.distance, 0),
        totalTime: dayData.reduce((sum, entry) => sum + entry.duration, 0),
        totalEmissions: dayData.reduce((sum, entry) => sum + parseFloat(entry.emissions), 0)
    };
}

function getWeeklySummary() {
    const today = new Date();
    const weekAgo = new Date(today);
    weekAgo.setDate(today.getDate() - 7);

    const weekData = foodData.filter(entry => {
        const entryDate = new Date(entry.date);
        return entryDate >= weekAgo && entryDate <= today;
    });

    const uniqueDays = [...new Set(weekData.map(entry => entry.date))].length;
    const totalCalories = weekData.reduce((sum, entry) => sum + entry.calories, 0);
    const avgCalories = uniqueDays > 0 ? Math.round(totalCalories / uniqueDays) : 0;

    return {
        daysLogged: uniqueDays,
        avgCaloriesPerDay: avgCalories
    };
}

function getTransportModeStats() {
    const modeStats = {};
    transportData.forEach(entry => {
        if (!modeStats[entry.mode]) {
            modeStats[entry.mode] = { count: 0, totalDistance: 0, totalTime: 0 };
        }
        modeStats[entry.mode].count++;
        modeStats[entry.mode].totalDistance += entry.distance;
        modeStats[entry.mode].totalTime += entry.duration;
    });
    return modeStats;
}

// Load sample data
function loadSampleData() {
    foodData = JSON.parse(JSON.stringify(sampleFoodData));
    transportData = JSON.parse(JSON.stringify(sampleTransportData)).map(entry => ({
        ...entry,
        emissions: calculateEmissions(entry.mode, entry.distance)
    }));
}

// Export functions for CSV
function exportFoodData() {
    if (foodData.length === 0) return null;

    let csv = 'Date,Meal Type,Food Item,Quantity (g),Calories\n';
    foodData.forEach(entry => {
        csv += `${entry.date},${entry.mealType},${entry.item},${entry.quantity},${entry.calories}\n`;
    });
    return csv;
}

function exportTransportData() {
    if (transportData.length === 0) return null;

    let csv = 'Date,Mode,Distance (km),Duration (min),Purpose,Emissions (kg CO2)\n';
    transportData.forEach(entry => {
        csv += `${entry.date},${entry.mode},${entry.distance},${entry.duration},${entry.purpose},${entry.emissions}\n`;
    });
    return csv;
}

// Persistent storage helpers (localStorage)
function saveDataToStorage() {
    try {
        localStorage.setItem('faculty_food_data', JSON.stringify(foodData));
        localStorage.setItem('faculty_transport_data', JSON.stringify(transportData));
    } catch (e) {
        console.warn('Failed to save data to localStorage', e);
    }
}

function loadDataFromStorage() {
    try {
        const f = localStorage.getItem('faculty_food_data');
        const t = localStorage.getItem('faculty_transport_data');
        if (f) {
            foodData = JSON.parse(f);
        } else {
            foodData = JSON.parse(JSON.stringify(sampleFoodData));
        }

        if (t) {
            transportData = JSON.parse(t);
        } else {
            transportData = JSON.parse(JSON.stringify(sampleTransportData)).map(entry => ({
                ...entry,
                emissions: calculateEmissions(entry.mode, entry.distance)
            }));
        }
    } catch (e) {
        console.warn('Failed to load data from localStorage', e);
        loadSampleData();
    }
}

// CSV compatibility wrappers used by some scripts
function exportFoodDataCSV() {
    return exportFoodData();
}

function exportTransportDataCSV() {
    return exportTransportData();
}

// Keep the left nav positioned below the header by exposing header height to CSS.
function syncHeaderHeightVar() {
    try {
        const header = document.querySelector('.main-header');
        if (!header) return;
        const rect = header.getBoundingClientRect();
        const height = Math.ceil(rect.height);
        document.documentElement.style.setProperty('--header-height', height + 'px');
    } catch (e) {
        // silent
    }
}

window.addEventListener('DOMContentLoaded', () => {
    syncHeaderHeightVar();
    // also update on a short delay to catch fonts/images
    setTimeout(syncHeaderHeightVar, 250);
    loadDataFromStorage();
});

window.addEventListener('resize', () => {
    syncHeaderHeightVar();
});