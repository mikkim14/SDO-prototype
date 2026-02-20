<?php
/**
 * Access Control and GHG Visibility Logic
 */

class AccessControl {
    const SCOPE_OFFICE = 'office';        // Campus + office records only
    const SCOPE_CAMPUS = 'campus';        // Campus-wide (all offices in campus)
    const SCOPE_GLOBAL = 'global';        // All campuses (system-wide)

    /**
     * Role to permissions map.
     * - can_input: modules this office may create/update/delete
     * - view_scope: office|campus|global visibility
     */
    private static $roleConfig = [
        'Environmental Management Unit' => [
            'can_input' => ['electricity', 'water', 'treated_water', 'waste_segregated', 'waste_unsegregated'],
            'view_scope' => self::SCOPE_CAMPUS
        ],
        'Resource Generation Office' => [
            'can_input' => ['lpg', 'food'],
            'view_scope' => self::SCOPE_OFFICE
        ],
        'General Services Office' => [
            'can_input' => ['fuel'],
            'view_scope' => self::SCOPE_OFFICE
        ],
        'Procurement Office' => [
            'can_input' => ['food'],
            'view_scope' => self::SCOPE_OFFICE
        ],
        'Sustainable Development Office' => [
            'can_input' => ['flight', 'accommodation'],
            'view_scope' => self::SCOPE_CAMPUS
        ],
        'Central Sustainable Office' => [
            'can_input' => [],
            'view_scope' => self::SCOPE_GLOBAL
        ],
    ];

    /**
     * Modules that store an office column and can be filtered down to the office level.
     */
    private static $modulesWithOfficeColumn = [
        'flight',
        'accommodation'
    ];

    /**
     * Get the visibility filter clause based on office, campus and module.
     *
     * Returns array with keys:
     *  - where_clause (string)
     *  - params (array)
     */
    public static function getGHGFilterClause($office, $campus, $module = null) {
        if (!$office) {
            return ['where_clause' => '', 'params' => []];
        }

        $scope = self::getViewScope($office);

        if ($scope === self::SCOPE_GLOBAL) {
            // Central: full visibility
            return ['where_clause' => '', 'params' => []];
        }

        if ($scope === self::SCOPE_CAMPUS) {
            // SDO: campus-wide (all offices)
            return ['where_clause' => 'WHERE campus = ?', 'params' => [$campus]];
        }

        // Default: office-scoped (campus + office when available)
        $hasOfficeColumn = $module ? in_array($module, self::$modulesWithOfficeColumn) : true;

        if ($hasOfficeColumn && $campus) {
            return [
                'where_clause' => 'WHERE campus = ? AND office = ?',
                'params' => [$campus, $office]
            ];
        }

        if ($campus) {
            return ['where_clause' => 'WHERE campus = ?', 'params' => [$campus]];
        }

        return ['where_clause' => '', 'params' => []];
    }

    /**
     * Bind filter params to a prepared statement.
     */
    public static function bindFilterParams($stmt, array $params) {
        if (empty($params)) {
            return;
        }

        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }

    /**
     * Fetch records for a table with a filter definition.
     */
    public static function fetchRecords($db, $table, array $filter, $orderBy = 'date DESC', $limit = 100) {
        $records = [];
        $query = "SELECT * FROM {$table}";
        if (!empty($filter['where_clause'])) {
            $query .= ' ' . $filter['where_clause'];
        }
        if ($orderBy) {
            $query .= ' ORDER BY ' . $orderBy;
        }
        if ($limit) {
            $query .= ' LIMIT ' . (int)$limit;
        }

        $stmt = $db->prepare($query);
        if (!$stmt) {
            return $records;
        }

        self::bindFilterParams($stmt, $filter['params']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $records = $result->fetch_all(MYSQLI_ASSOC);
        }
        $stmt->close();

        return $records;
    }

    /**
     * Whether the office can create/update/delete the specified module.
     */
    public static function canInput($office, $module) {
        $config = self::$roleConfig[$office] ?? ['can_input' => []];
        return in_array($module, $config['can_input']);
    }

    /**
     * Get the visibility scope for an office.
     */
    public static function getViewScope($office) {
        $config = self::$roleConfig[$office] ?? null;
        return $config['view_scope'] ?? self::SCOPE_OFFICE;
    }

    /**
     * Check if office can view all campuses.
     */
    public static function canViewAllCampuses($office) {
        return self::getViewScope($office) === self::SCOPE_GLOBAL;
    }

    /**
     * Check if office is restricted to own office GHG only.
     */
    public static function isOfficeModulesOnly($office) {
        return self::getViewScope($office) === self::SCOPE_OFFICE;
    }

    /**
     * Check if office can view campus-wide GHG.
     */
    public static function canViewCampusWideGHG($office) {
        return self::getViewScope($office) === self::SCOPE_CAMPUS;
    }

    /**
     * Get office description
     */
    public static function getOfficeDescription($office) {
        $descriptions = [
            'Environmental Management Unit' => 'EMU - Electricity, Water, Treated Water, Waste',
            'Resource Generation Office' => 'RGO - LPG, Food Consumption',
            'General Services Office' => 'GSO - Fuel Emissions',
            'Procurement Office' => 'PO - Food Consumption',
            'Sustainable Development Office' => 'SDO - Flight, Accommodation, Campus-wide GHG',
            'Central Sustainable Office' => 'CSD - System-wide Administration',
        ];
        
        return $descriptions[$office] ?? $office;
    }

    /**
     * Calculate role-based GHG totals and tree offset
     * Returns: ['total_kg_co2' => float, 't_co2' => float, 'tree_offset' => int, 'debug' => array]
     */
    public static function getRoleBasedGHGTotals($db, $office, $campus, $year = null) {
        $scope = self::getViewScope($office);
        
        // Determine campus filter based on scope
        $campus_filter = null;
        if ($scope === self::SCOPE_OFFICE || $scope === self::SCOPE_CAMPUS) {
            $campus_filter = $campus;
        }
        // SCOPE_GLOBAL: no campus filter
        
        // Get breakdown by category (this handles all the complex filtering)
        $breakdown = self::getGHGBreakdownByCategory($db, $campus_filter, $year);
        
        // For office scope, only sum categories they can input
        $config = self::$roleConfig[$office] ?? ['can_input' => []];
        $can_input = $config['can_input'];
        $include_all = ($scope === self::SCOPE_CAMPUS || $scope === self::SCOPE_GLOBAL);
        
        // Category to permission mapping
        $category_permissions = [
            'electricity' => 'electricity',
            'water' => 'water',
            'treated_water' => 'treated_water',
            'waste_segregated' => 'waste_segregated',
            'waste_unsegregated' => 'waste_unsegregated',
            'lpg' => 'lpg',
            'food' => 'food',
            'fuel' => 'fuel',
            'flight' => 'flight',
            'accommodation' => 'accommodation'
        ];
        
        $total_kg_co2 = 0;
        $debug = [];
        
        // Sum up emissions from breakdown based on permissions
        foreach ($breakdown as $category_key => $data) {
            $permission = $category_permissions[$category_key] ?? null;
            
            // Include if office has permission OR if viewing campus/global scope
            if ($include_all || ($permission && in_array($permission, $can_input))) {
                $total_kg_co2 += $data['kg_co2'];
                $debug[$category_key] = [
                    'records' => $data['records'],
                    'consumption' => $data['consumption'],
                    'kg_co2' => $data['kg_co2']
                ];
            }
        }

        // Calculate tonnes and tree offset
        $t_co2 = $total_kg_co2 / 1000;
        $tree_offset = ceil($total_kg_co2 / 21); // 21 kg CO2 per tree per year

        $debug['office'] = $office;
        $debug['campus'] = $campus;
        $debug['campus_filter'] = $campus_filter;
        $debug['year'] = $year;
        $debug['scope'] = $scope;

        return [
            'total_kg_co2' => round($total_kg_co2, 2),
            't_co2' => round($t_co2, 4),
            'tree_offset' => $tree_offset,
            'debug' => $debug
        ];
    }

    /**
     * Get GHG breakdown by category for CSD dashboard
     * Returns detailed emissions per category with record counts
     */
    public static function getGHGBreakdownByCategory($db, $campus_filter = null, $year_filter = null) {
        $breakdown = [];
        
        // Define emission factors
        $ELECTRICITY_FACTOR = 0.7264;
        $WATER_FACTOR = 0.344;
        $TREATED_WATER_FACTOR = 1.062;
        $LPG_FACTOR = 3.0;
        $FOOD_FACTOR = 0.5;
        $FUEL_FACTOR = 2.31;
        $WASTE_FACTOR = 0.5;
        $FLIGHT_FACTOR = 0.24;
        $ACCOMMODATION_FACTOR = 10;

        // Helper function to build WHERE clause for each table
        $buildWhere = function($campus_col = 'campus', $year_col = 'year') use ($campus_filter, $year_filter) {
            $parts = [];
            $params = [];
            $types = '';
            
            if ($campus_filter) {
                $parts[] = "$campus_col = ?";
                $params[] = $campus_filter;
                $types .= 's';
            }
            if ($year_filter && $year_col !== null) {
                $parts[] = "$year_col = ?";
                $params[] = $year_filter;
                $types .= 's';
            }
            
            $where = count($parts) > 0 ? 'WHERE ' . implode(' AND ', $parts) : '';
            return [$where, $types, $params];
        };
        
        // Electricity
        list($where, $types, $params) = $buildWhere('campus', 'year');
        $query = "SELECT COUNT(*) as records, SUM(IFNULL(consumption,0)) as total FROM electricity_consumption $where";
        $stmt = $db->prepare($query);
        if ($stmt) {
            if (count($params) > 0) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $consumption = $row['total'] ?? 0;
                $breakdown['electricity'] = [
                    'name' => 'Electricity',
                    'records' => $row['records'],
                    'consumption' => $consumption,
                    'unit' => 'kWh',
                    'kg_co2' => $consumption * $ELECTRICITY_FACTOR,
                    'office' => 'EMU',
                    'scope' => 'Scope 2'
                ];
            }
            $stmt->close();
        }

        // Water (has Date column for year filtering)
        list($where, $types, $params) = $buildWhere('Campus', null);
        if ($year_filter) {
            $where = $where ? "$where AND YEAR(Date) = ?" : "WHERE YEAR(Date) = ?";
            $types .= 's';
            $params[] = $year_filter;
        }
        $query = "SELECT COUNT(*) as records, SUM(IFNULL(Consumption,0)) as total FROM tblwater $where";
        $stmt = $db->prepare($query);
        if ($stmt) {
            if (count($params) > 0) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $amount = $row['total'] ?? 0;
                $breakdown['water'] = [
                    'name' => 'Water',
                    'records' => $row['records'],
                    'consumption' => $amount,
                    'unit' => 'm³',
                    'kg_co2' => $amount * $WATER_FACTOR,
                    'office' => 'EMU',
                    'scope' => 'Scope 3'
                ];
            }
            $stmt->close();
        }

        // Treated Water (Month column, no year filtering)
        list($where, $types, $params) = $buildWhere('Campus', null);
        $query = "SELECT COUNT(*) as records, SUM(IFNULL(TreatedWaterVolume,0)) as total FROM tbltreatedwater $where";
        $stmt = $db->prepare($query);
        if ($stmt) {
            if (count($params) > 0) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $amount = $row['total'] ?? 0;
                $breakdown['treated_water'] = [
                    'name' => 'Treated Water',
                    'records' => $row['records'],
                    'consumption' => $amount,
                    'unit' => 'm³',
                    'kg_co2' => $amount * $TREATED_WATER_FACTOR,
                    'office' => 'EMU',
                    'scope' => 'Scope 3'
                ];
            }
            $stmt->close();
        }

        // Waste Segregated
        list($where, $types, $params) = $buildWhere('Campus', 'Year');
        $query = "SELECT COUNT(*) as records, SUM(IFNULL(QuantityInKG,0)) as total FROM tblsolidwastesegregated $where";
        $stmt = $db->prepare($query);
        if ($stmt) {
            if (count($params) > 0) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $amount = $row['total'] ?? 0;
                $breakdown['waste_segregated'] = [
                    'name' => 'Waste Segregated',
                    'records' => $row['records'],
                    'consumption' => $amount,
                    'unit' => 'kg',
                    'kg_co2' => $amount * $WASTE_FACTOR,
                    'office' => 'EMU',
                    'scope' => 'Scope 3'
                ];
            }
            $stmt->close();
        }

        // Waste Unsegregated (has Year column)
        list($where, $types, $params) = $buildWhere('Campus', 'Year');
        $query = "SELECT COUNT(*) as records, SUM(IFNULL(QuantityInKG,0)) as total FROM tblsolidwasteunsegregated $where";
        $stmt = $db->prepare($query);
        if ($stmt) {
            if (count($params) > 0) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $amount = $row['total'] ?? 0;
                $breakdown['waste_unsegregated'] = [
                    'name' => 'Waste Unsegregated',
                    'records' => $row['records'],
                    'consumption' => $amount,
                    'unit' => 'kg',
                    'kg_co2' => $amount * $WASTE_FACTOR,
                    'office' => 'EMU',
                    'scope' => 'Scope 3'
                ];
            }
            $stmt->close();
        }

        // LPG
        list($where, $types, $params) = $buildWhere('Campus', 'YearTransact');
        $query = "SELECT COUNT(*) as records, SUM(TotalTankVolume) as total FROM tbllpg $where";
        $stmt = $db->prepare($query);
        if (count($params) > 0) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $amount = $row['total'] ?? 0;
            $breakdown['lpg'] = [
                'name' => 'LPG',
                'records' => $row['records'],
                'consumption' => $amount,
                'unit' => 'kg',
                'kg_co2' => $amount * $LPG_FACTOR,
                'office' => 'RGO',
                'scope' => 'Scope 1'
            ];
        }
        $stmt->close();

        // Food
        list($where, $types, $params) = $buildWhere('Campus', 'YearTransaction');
        $query = "SELECT COUNT(*) as records, SUM(QuantityOfServing) as total FROM tblfoodwaste $where";
        $stmt = $db->prepare($query);
        if (count($params) > 0) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $amount = $row['total'] ?? 0;
            $breakdown['food'] = [
                'name' => 'Food',
                'records' => $row['records'],
                'consumption' => $amount,
                'unit' => 'servings',
                'kg_co2' => $amount * $FOOD_FACTOR,
                'office' => 'PO',
                'scope' => 'Scope 3'
            ];
        }
        $stmt->close();

        // Fuel (date column, no year - extract from date)
        list($where, $types, $params) = $buildWhere('campus', null);
        if ($year_filter) {
            $where = $where ? $where . " AND YEAR(date) = ?" : "WHERE YEAR(date) = ?";
            $types .= 's';
            $params[] = $year_filter;
        }
        $query = "SELECT COUNT(*) as records, SUM(quantity_liters) as total FROM fuel_emissions $where";
        $stmt = $db->prepare($query);
        if (count($params) > 0) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $amount = $row['total'] ?? 0;
            $breakdown['fuel'] = [
                'name' => 'Fuel',
                'records' => $row['records'],
                'consumption' => $amount,
                'unit' => 'liters',
                'kg_co2' => $amount * $FUEL_FACTOR,
                'office' => 'GSO',
                'scope' => 'Scope 1'
            ];
        }
        $stmt->close();

        // Flight
        list($where, $types, $params) = $buildWhere('Campus', 'Year');
        $query = "SELECT COUNT(*) as records, SUM(GHGEmissionKGC02e) as total_emissions FROM tblflight $where";
        $stmt = $db->prepare($query);
        if (count($params) > 0) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $emissions = $row['total_emissions'] ?? 0;
            $breakdown['flight'] = [
                'name' => 'Flight',
                'records' => $row['records'],
                'consumption' => $row['records'],
                'unit' => 'trips',
                'kg_co2' => $emissions,
                'office' => 'SDO',
                'scope' => 'Scope 3'
            ];
        }
        $stmt->close();

        // Accommodation
        list($where, $types, $params) = $buildWhere('Campus', 'YearTransact');
        $query = "SELECT COUNT(*) as records, SUM(NumOccupiedRoom * NumNightPerRoom) as total, SUM(GHGEmissionKGC02e) as total_emissions FROM tblaccommodation $where";
        $stmt = $db->prepare($query);
        if (count($params) > 0) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $amount = $row['total'] ?? 0;
            $emissions = $row['total_emissions'] ?? 0;
            $breakdown['accommodation'] = [
                'name' => 'Accommodation',
                'records' => $row['records'],
                'consumption' => $amount,
                'unit' => 'guest-nights',
                'kg_co2' => $emissions,
                'office' => 'SDO',
                'scope' => 'Scope 3'
            ];
        }
        $stmt->close();

        return $breakdown;
    }
}
?>
