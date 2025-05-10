<?php

class Metric
{
    protected $_sum;
    protected $_count;
    protected $_min;
    protected $_max;
    protected $_deviationSum;

    public function __construct()
    {
        $this->reset();
    }

    public function reset()
    {
        $this->_sum = 0;
        $this->_count = 0;
        $this->_min = 0;
        $this->_max = 0;
    }

    public function update($value)
    {
        $this->_sum += $value;
        $this->_count++;
        if($this->_min == 0 || $value < $this->_min)
        {
            $this->_min = $value;
        }
        if($value > $this->_max)
        {
            $this->_max = $value;
        }
    }

    public function total()
    {
        return $this->_sum;
    }

    public function count()
    {
        return $this->_count;
    }

    public function min()
    {
        return $this->_min;
    }

    public function max()
    {
        return $this->_max;
    }

    public function average()
    {
        $ret = 0;

        if($this->_count != 0)
        {
            $ret = $this->_sum / $this->_count;
        }

        return $ret;
    }

    public function extremeSpread()
    {
        return $this->_max - $this->_min;
    }

    public function standardDeviation()
    {
        return 0.0;
    }

    public function toArray()
    {
        $ret =
        [
            'sum'   => $this->_sum,
            'count' => $this->_count,
            'min'   => $this->_min,
            'max'   => $this->_max,
            'ds'    => $this->_deviationSum,
        ];

        return $ret;
    }

    public function toObject()
    {
        return (object)$this->toArray();
    }
}

class MetricSet
{
    protected $_metrics;

    public function __construct()
    {
        $this->_metrics = [];
    }

    public function resetAll()
    {
        foreach($this->_metrics as $index => $metric)
        {
            $this->_metrics[$index]->reset();
        }
    }

    public function resetMetric($name)
    {
        if(isset($this->_metrics[$name]))
        {
            $this->_metrics[$name]->reset();
        }
    }

    public function addMetric($name)
    {
        if(!isset($this->_metrics[$name]))
        {
            $this->_metrics[$name] = new Metric();
        }
    }

    public function getMetric($name)
    {
        if(isset($this->_metrics[$name]))
        {
            return $this->_metrics[$name];
        }

        return null;
    }

    public function updateMetric($name, $value)
    {
        $ret = false;

        if(isset($this->_metrics[$name]))
        {
            $this->_metrics[$name]->update($value);
            $ret = true;
        }

        return $ret;
    }

    public function toArray()
    {
        $ret = [];

        foreach($this->_metrics as $name => $metric)
        {
            $ret[$name] = $metric->toArray();
        }

        return $ret;
    }

    public function toObject()
    {
        return (object)$this->toArray();
    }

    public function toJSON()
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}
