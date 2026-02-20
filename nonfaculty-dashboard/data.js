// Non-faculty Personal Data Structures

// Food consumption data
// Format: { date, mealType, item, quantity, calories }
let foodData = [];

// Transportation data
// Format: { date, mode, distance, duration, purpose, emissions }
let transportData = [];

// Transport emission factors (kg CO2 per km and per minute for demo)
const EMISSION_FACTORS = {
	'Walking': 0,
	'Biking': 0,
	'Bus': 0.08,
	'Car': 0.15,
	'Motorcycle': 0.10,
	'Other': 0.12
};
const EMISSION_RATE_PER_MINUTE = {
	'Bus': 0.01,
	'Car': 0.02,
	'Motorcycle': 0.015,
	'Other': 0.012
};

// Calculate emissions for transport entry (based on minutes)
function calculateEmissions(mode, duration) {
	const rate = EMISSION_RATE_PER_MINUTE[mode] || 0;
	return (rate * duration).toFixed(3);
}

// Validation functions
function validateTransportEntry(date, mode, distance, duration, purpose) {
	const errors = [];
	if (!date) errors.push('Date is required');
	if (!mode) errors.push('Transport mode is required');
	if (!distance || distance < 0) errors.push('Distance must be 0 or greater');
	if (!duration || duration <= 0) errors.push('Duration must be greater than 0');
	if (!purpose || purpose.trim().length === 0) errors.push('Purpose is required');
	return errors;
}

// Data processing functions
function getDailyTransportSummary(date) {
	const dayData = transportData.filter(entry => entry.date === date);
	return {
		totalDistance: dayData.reduce((sum, entry) => sum + entry.distance, 0),
		totalTime: dayData.reduce((sum, entry) => sum + entry.duration, 0),
		totalEmissions: dayData.reduce((sum, entry) => sum + parseFloat(entry.emissions), 0)
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

// Persistent storage helpers (localStorage)
function saveDataToStorage() {
	try {
		localStorage.setItem('nonfaculty_transport_data', JSON.stringify(transportData));
	} catch (e) {
		console.warn('Failed to save data to localStorage', e);
	}
}

function loadDataFromStorage() {
	try {
		const t = localStorage.getItem('nonfaculty_transport_data');
		if (t) {
			transportData = JSON.parse(t);
		}
	} catch (e) {
		console.warn('Failed to load data from localStorage', e);
	}
}

function exportTransportDataCSV() {
	if (transportData.length === 0) return null;
	let csv = 'Date,Mode,Distance (km),Duration (min),Purpose,Emissions (kg CO2)\n';
	transportData.forEach(entry => {
		csv += `${entry.date},${entry.mode},${entry.distance},${entry.duration},${entry.purpose},${entry.emissions}\n`;
	});
	return csv;
}

window.addEventListener('DOMContentLoaded', loadDataFromStorage);
