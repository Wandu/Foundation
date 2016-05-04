<?php
namespace Wandu\Foundation\Kernels;

use Wandu\Foundation\KernelInterface;
use Wandu\DI\ContainerInterface;

class NullKernel implements KernelInterface
{
    public function boot(ContainerInterface $app)
    {
    }

    public function execute(ContainerInterface $app)
    {
    }
}