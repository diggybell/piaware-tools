<?php

/**
    \file Metric.php
    \brief Contains the Metric class
*/

/**
    \class Metric
    \brief Encapsulates basic statistical calculations for min/max/average/es
    \ingroup Lib
*/
class Metric
{
    protected $_sum;            ///< The sum of all updates
    protected $_count;          ///< The number of updates
    protected $_min;            ///< The minimum value seen
    protected $_max;            ///< The maximum value seen
    protected $_deviationSum;   ///< Future

    /**
        \brief Constructor
    */
    public function __construct()
    {
        $this->reset();
    }

    /**
        \brief Reset this metric
    */
    public function reset()
    {
        $this->_sum = 0;
        $this->_count = 0;
        $this->_min = 0;
        $this->_max = 0;
    }

    /**
        \brief Update the metric
        \param $value The value to update the metric with
    */
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

    /**
        \brief Get the total for the metric
        \returns The total for the metric
    */
    public function total()
    {
        return $this->_sum;
    }

    /**
        \brief Get the count for the metric
        \returns The count for the metric
    */
    public function count()
    {
        return $this->_count;
    }

    /**
        \brief Get the minimum for the metric
        \returns The minimum for the metric
    */
    public function min()
    {
        return $this->_min;
    }

    /**
        \brief Get the maximum for the metric
        \returns The maximum for the metric
    */
    public function max()
    {
        return $this->_max;
    }

    /**
        \brief Get the average for the metric
        \returns The average for the metric
    */
    public function average()
    {
        $ret = 0;

        if($this->_count != 0)
        {
            $ret = $this->_sum / $this->_count;
        }

        return $ret;
    }

    /**
        \brief Get the extreme spread for the metric
        \returns The extreme spread for the metric
    */
    public function extremeSpread()
    {
        return $this->_max - $this->_min;
    }

    /**
        \brief Get the standard deviation for the metric (Future)
        \returns The standard deviation for the metric
    */
    public function standardDeviation()
    {
        return 0.0;
    }

    /**
        \brief Get the metric contents as an array
        \returns An array containing the metric values
    */
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

    /**
        \brief Get the metric contents as an object
        \returns An object containing the metric values
    */
    public function toObject()
    {
        return (object)$this->toArray();
    }
}

