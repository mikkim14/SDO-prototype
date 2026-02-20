<?php
/**
 * GHG Emissions Calculator
 * Calculates CO2, tCO2, and tree offset values
 */

class GHGCalculator {
    const DEFAULT_KG_CO2_PER_KWH = 0.7264; // Electricity emission factor
    const CO2_PER_TREE_PER_YEAR = 21; // kg CO2
    
    /**
     * Calculate electricity consumption (kWh)
     */
    public static function calculateElectricityConsumption($prev_reading, $current_reading, $multiplier = 1) {
        return ($current_reading - $prev_reading) * $multiplier;
    }
    
    /**
     * Calculate kg CO2 from consumption
     */
    public static function calculateKgCO2($consumption, $kg_co2_per_kwh = self::DEFAULT_KG_CO2_PER_KWH) {
        if ($consumption <= 0) return 0;
        return round($consumption * $kg_co2_per_kwh, 2);
    }
    
    /**
     * Convert kg CO2 to tCO2
     */
    public static function convertToTonnes($kg_co2) {
        if ($kg_co2 <= 0) return 0;
        return round($kg_co2 / 1000, 4);
    }
    
    /**
     * Calculate tree offset needed to offset CO2
     */
    public static function calculateTreeOffset($kg_co2) {
        if ($kg_co2 <= 0) return 0;
        return ceil($kg_co2 / self::CO2_PER_TREE_PER_YEAR);
    }
    
    /**
     * Get all GHG metrics in one call
     */
    public static function calculateAll($consumption, $kg_co2_per_kwh = self::DEFAULT_KG_CO2_PER_KWH) {
        $kg_co2 = self::calculateKgCO2($consumption, $kg_co2_per_kwh);
        $t_co2 = self::convertToTonnes($kg_co2);
        $tree_offset = self::calculateTreeOffset($kg_co2);
        
        return [
            'consumption' => round($consumption, 2),
            'kg_co2' => $kg_co2,
            't_co2' => $t_co2,
            'tree_offset' => $tree_offset
        ];
    }
}
?>
