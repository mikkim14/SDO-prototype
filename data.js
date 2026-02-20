// Resource Consumption Categories
const CONSUMPTION_CATEGORIES = [
    'Water consumption',
    'Treated water',
    'Solid waste segregated',
    'Solid waste unsegregated',
    'Food consumption',
    'Flight emission',
    'Accommodation emission'
];

// Sample resource consumption data
// Format: { year, month?, emission, category, source }
const sampleData = [
    // Water consumption
    { year: 2015, emission: 50000, category: 'Water consumption', source: 'Main Campus' },
    { year: 2016, emission: 52000, category: 'Water consumption', source: 'Main Campus' },
    { year: 2017, emission: 54000, category: 'Water consumption', source: 'Main Campus' },
    { year: 2018, emission: 56000, category: 'Water consumption', source: 'Main Campus' },
    { year: 2019, emission: 58000, category: 'Water consumption', source: 'Main Campus' },
    { year: 2020, emission: 56000, category: 'Water consumption', source: 'Main Campus' },
    { year: 2021, emission: 57000, category: 'Water consumption', source: 'Main Campus' },
    { year: 2022, emission: 58500, category: 'Water consumption', source: 'Main Campus' },
    { year: 2023, emission: 60000, category: 'Water consumption', source: 'Main Campus' },
    
    // Treated water
    { year: 2015, emission: 8000, category: 'Treated water', source: 'Treatment Plant' },
    { year: 2016, emission: 8300, category: 'Treated water', source: 'Treatment Plant' },
    { year: 2017, emission: 8600, category: 'Treated water', source: 'Treatment Plant' },
    { year: 2018, emission: 8900, category: 'Treated water', source: 'Treatment Plant' },
    { year: 2019, emission: 9200, category: 'Treated water', source: 'Treatment Plant' },
    { year: 2020, emission: 9000, category: 'Treated water', source: 'Treatment Plant' },
    { year: 2021, emission: 9100, category: 'Treated water', source: 'Treatment Plant' },
    { year: 2022, emission: 9300, category: 'Treated water', source: 'Treatment Plant' },
    { year: 2023, emission: 9400, category: 'Treated water', source: 'Treatment Plant' },
    
    // Solid waste segregated
    { year: 2015, emission: 12000, category: 'Solid waste segregated', source: 'Waste Management' },
    { year: 2016, emission: 12500, category: 'Solid waste segregated', source: 'Waste Management' },
    { year: 2017, emission: 13000, category: 'Solid waste segregated', source: 'Waste Management' },
    { year: 2018, emission: 13500, category: 'Solid waste segregated', source: 'Waste Management' },
    { year: 2019, emission: 14000, category: 'Solid waste segregated', source: 'Waste Management' },
    { year: 2020, emission: 13800, category: 'Solid waste segregated', source: 'Waste Management' },
    { year: 2021, emission: 14200, category: 'Solid waste segregated', source: 'Waste Management' },
    { year: 2022, emission: 14500, category: 'Solid waste segregated', source: 'Waste Management' },
    { year: 2023, emission: 14800, category: 'Solid waste segregated', source: 'Waste Management' },
    
    // Solid waste unsegregated
    { year: 2015, emission: 5000, category: 'Solid waste unsegregated', source: 'Waste Management' },
    { year: 2016, emission: 5200, category: 'Solid waste unsegregated', source: 'Waste Management' },
    { year: 2017, emission: 5400, category: 'Solid waste unsegregated', source: 'Waste Management' },
    { year: 2018, emission: 5600, category: 'Solid waste unsegregated', source: 'Waste Management' },
    { year: 2019, emission: 5800, category: 'Solid waste unsegregated', source: 'Waste Management' },
    { year: 2020, emission: 5700, category: 'Solid waste unsegregated', source: 'Waste Management' },
    { year: 2021, emission: 5750, category: 'Solid waste unsegregated', source: 'Waste Management' },
    { year: 2022, emission: 5900, category: 'Solid waste unsegregated', source: 'Waste Management' },
    { year: 2023, emission: 6000, category: 'Solid waste unsegregated', source: 'Waste Management' },
    
    // Food consumption
    { year: 2015, emission: 15000, category: 'Food consumption', source: 'Cafeteria' },
    { year: 2016, emission: 15500, category: 'Food consumption', source: 'Cafeteria' },
    { year: 2017, emission: 16000, category: 'Food consumption', source: 'Cafeteria' },
    { year: 2018, emission: 16500, category: 'Food consumption', source: 'Cafeteria' },
    { year: 2019, emission: 17000, category: 'Food consumption', source: 'Cafeteria' },
    { year: 2020, emission: 16500, category: 'Food consumption', source: 'Cafeteria' },
    { year: 2021, emission: 16800, category: 'Food consumption', source: 'Cafeteria' },
    { year: 2022, emission: 17200, category: 'Food consumption', source: 'Cafeteria' },
    { year: 2023, emission: 17500, category: 'Food consumption', source: 'Cafeteria' },
    
    // Flight emission
    { year: 2015, emission: 8000, category: 'Flight emission', source: 'Staff Travel' },
    { year: 2016, emission: 8500, category: 'Flight emission', source: 'Staff Travel' },
    { year: 2017, emission: 9000, category: 'Flight emission', source: 'Staff Travel' },
    { year: 2018, emission: 9500, category: 'Flight emission', source: 'Staff Travel' },
    { year: 2019, emission: 10000, category: 'Flight emission', source: 'Staff Travel' },
    { year: 2020, emission: 7000, category: 'Flight emission', source: 'Staff Travel' },
    { year: 2021, emission: 7500, category: 'Flight emission', source: 'Staff Travel' },
    { year: 2022, emission: 8000, category: 'Flight emission', source: 'Staff Travel' },
    { year: 2023, emission: 8500, category: 'Flight emission', source: 'Staff Travel' },
    
    // Accommodation emission
    { year: 2015, emission: 10000, category: 'Accommodation emission', source: 'Faculty Housing' },
    { year: 2016, emission: 10500, category: 'Accommodation emission', source: 'Faculty Housing' },
    { year: 2017, emission: 11000, category: 'Accommodation emission', source: 'Faculty Housing' },
    { year: 2018, emission: 11500, category: 'Accommodation emission', source: 'Faculty Housing' },
    { year: 2019, emission: 12000, category: 'Accommodation emission', source: 'Faculty Housing' },
    { year: 2020, emission: 11800, category: 'Accommodation emission', source: 'Faculty Housing' },
    { year: 2021, emission: 12000, category: 'Accommodation emission', source: 'Faculty Housing' },
    { year: 2022, emission: 12200, category: 'Accommodation emission', source: 'Faculty Housing' },
    { year: 2023, emission: 12500, category: 'Accommodation emission', source: 'Faculty Housing' }
];

// Validate data entry
function validateDataEntry(year, emission, category) {
    const errors = [];
    
    if (!year || year < 2000 || year > 2100) {
        errors.push('Year must be between 2000 and 2100');
    }
    if (!emission || emission < 0) {
        errors.push('Consumption must be a positive number (kg)');
    }
    if (!category || !CONSUMPTION_CATEGORIES.includes(category)) {
        errors.push('Invalid category selected');
    }
    
    return errors;
}

// Calculate statistics
function calculateStatistics(data) {
    if (data.length === 0) return null;
    
    const years = [...new Set(data.map(d => d.year))].sort();
    const yearlyEmissions = years.map(year => ({
        year,
        total: data.filter(d => d.year === year).reduce((sum, d) => sum + d.emission, 0)
    }));
    
    const totalEmissions = yearlyEmissions.reduce((sum, y) => sum + y.total, 0);
    const avgEmission = totalEmissions / yearlyEmissions.length;
    
    // Calculate growth rates
    let totalGrowthRate = 0;
    if (yearlyEmissions.length > 1) {
        const firstYear = yearlyEmissions[0].total;
        const lastYear = yearlyEmissions[yearlyEmissions.length - 1].total;
        totalGrowthRate = ((lastYear - firstYear) / firstYear) * 100;
    }
    
    // YoY change (most recent year)
    let yoyChange = 0;
    if (yearlyEmissions.length >= 2) {
        const recent = yearlyEmissions[yearlyEmissions.length - 1].total;
        const previous = yearlyEmissions[yearlyEmissions.length - 2].total;
        yoyChange = ((recent - previous) / previous) * 100;
    }
    
    return {
        totalEmissions,
        avgEmission,
        totalGrowthRate,
        yoyChange,
        yearsInData: yearlyEmissions.length,
        yearlyEmissions
    };
}
