<?php

include_once('Metric.php');

/**
    \file MetricSet.php
    \brief Containes the MetricSet class
*/

/**
    \class MetricSet
    \brief This object encapsulates a set of metrics to be manipulated together
    \ingroup Lib
*/
class MetricSet
{
    protected $_metrics;            ///< The array of metrics being managed

    /**
        \brief Constructor
    */
    public function __construct()
    {
        $this->_metrics = [];
    }

    /**
        \brief Reset all of the metrics in the set
    */
    public function resetAll()
    {
        foreach($this->_metrics as $index => $metric)
        {
            $this->_metrics[$index]->reset();
        }
    }

    /**
        \brief Reset a single metric in the set
        \param $name The name of the metric to be reset
    */
    public function resetMetric($name)
    {
        if(isset($this->_metrics[$name]))
        {
            $this->_metrics[$name]->reset();
        }
    }

    /**
        \brief Add a metric to the set
        \param $name The name of the metric to be added
    */
    public function addMetric($name)
    {
        if(!isset($this->_metrics[$name]))
        {
            $this->_metrics[$name] = new Metric();
        }
    }

    /**
        \brief Get a metric object
        \param $name The name of the metric to be retrieved
        \retval null The metric does not exist
        \returns The metric object for name
        \details This method returns the metric object. It is generally used as $metricSet->getMetric('foo')->average().
    */
    public function getMetric($name)
    {
        if(isset($this->_metrics[$name]))
        {
            return $this->_metrics[$name];
        }

        return null;
    }

    /**
        \brief Update a metric in the metric set
        \param $name The name of the metric to update
        \retval true The metric was updated
        \retval false The metric does not exist
        \returns Status of update
    */
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

    /**
        \brief Retrieve the metric set as an array
        \returns The metric set as an array
    */
    public function toArray()
    {
        $ret = [];

        foreach($this->_metrics as $name => $metric)
        {
            $ret[$name] = $metric->toArray();
        }

        return $ret;
    }

    /**
        \brief Retrieve the metric set as an object
        \returns The metric set as an object
    */
    public function toObject()
    {
        return (object)$this->toArray();
    }

    /**
        \brief Retriev the metric set as a JSON string
        \returns JSON string containing metric data
    */
    public function toJSON()
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}
