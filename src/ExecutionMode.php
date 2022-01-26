<?php

namespace Tideways\Shopware6Benchmarking;

enum ExecutionMode : string
{
    case DOCKER = 'docker';
    case LOCAL = 'local';
}