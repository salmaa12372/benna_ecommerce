<?php
// model/PlanRepas.php
class PlanRepas {
    public $id, $plan_id, $jour, $moment, $description, $calories;

    // moment: matin | midi | soir | collation
    public function __construct($plan_id, $jour, $moment, $description, $calories = 0) {
        $this->plan_id     = $plan_id;
        $this->jour        = $jour;
        $this->moment      = $moment;
        $this->description = $description;
        $this->calories    = $calories;
    }
}
