<?php
/**
 * Alert System
 * 
 * Manages alert rules and triggers notifications based on monitored metrics and errors.
 */
class AlertSystem {

    private static $alertRules = [];
    private static $triggeredAlerts = [];

    /**
     * Defines an alert rule.
     * @param string $ruleName A unique name for the rule.
     * @param array $ruleConfig Configuration for the rule (e.g., ['metric' => 'cpu_usage', 'threshold' => 80, 'operator' => '>']).
     */
    public static function defineRule($ruleName, $ruleConfig) {
        self::$alertRules[$ruleName] = $ruleConfig;
    }

    /**
     * Evaluates all defined alert rules against current metrics and triggers alerts.
     * @param array $metrics An associative array of current metrics (e.g., from PerformanceMonitor).
     * @param array $errors An array of recent errors (e.g., from ErrorTracker).
     */
    public static function evaluateRules($metrics, $errors) {
        foreach (self::$alertRules as $ruleName => $ruleConfig) {
            $alertTriggered = false;

            switch ($ruleConfig['type']) {
                case 'metric_threshold':
                    if (isset($metrics[$ruleConfig['metric']])) {
                        $value = $metrics[$ruleConfig['metric']];
                        $threshold = $ruleConfig['threshold'];
                        $operator = $ruleConfig['operator'];

                        // Simple evaluation logic
                        if (($operator === '>' && $value > $threshold) ||
                            ($operator === '<' && $value < $threshold) ||
                            ($operator === '>=' && $value >= $threshold) ||
                            ($operator === '<=' && $value <= $threshold) ||
                            ($operator === '==' && $value == $threshold)) {
                            $alertTriggered = true;
                        }
                    }
                    break;
                case 'error_rate':
                    $errorCount = count($errors);
                    if ($errorCount > $ruleConfig['threshold']) {
                        $alertTriggered = true;
                    }
                    break;
                case 'custom':
                    // For custom rules, assume a callback or more complex logic
                    // For this example, we'll just check a boolean flag if provided
                    if (isset($ruleConfig['condition']) && $ruleConfig['condition'] === true) {
                        $alertTriggered = true;
                    }
                    break;
            }

            if ($alertTriggered) {
                self::triggerAlert($ruleName, $ruleConfig);
            }
        }
    }

    /**
     * Triggers an alert and logs it.
     * @param string $ruleName The name of the rule that triggered the alert.
     * @param array $ruleConfig The configuration of the triggered rule.
     */
    public static function triggerAlert($ruleName, $ruleConfig) {
        $alert = [
            'rule_name' => $ruleName,
            'timestamp' => date('Y-m-d H:i:s'),
            'config' => $ruleConfig,
            'status' => 'triggered'
        ];
        self::$triggeredAlerts[] = $alert;

        // Log the alert to a file
        $logMessage = "ALERT TRIGGERED: Rule '" . $ruleName . "' at " . $alert['timestamp'] . "\n";
        $logMessage .= "  Config: " . json_encode($ruleConfig) . "\n";
        file_put_contents('logs/alerts.log', $logMessage, FILE_APPEND);

        // In a real system, this would send notifications (email, SMS, etc.)
        // self::sendNotification($alert);
    }

    /**
     * Retrieves all triggered alerts.
     * @return array
     */
    public static function getTriggeredAlerts() {
        return self::$triggeredAlerts;
    }

    /**
     * Clears all triggered alerts.
     */
    public static function clearTriggeredAlerts() {
        self::$triggeredAlerts = [];
    }
}
?>