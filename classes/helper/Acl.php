<?php

/**
 * @author Vladyslav Zhyokin
 */

/**
 * @brief Access Control List implementation for the BIM system.
 *
 * This class extends Zend_Acl to provide role-based access control for
 * the Barcode Instruction Management system. It defines roles, resources,
 * and permissions that determine which users have access to which parts
 * of the application.
 * 
 * @class Helper_Acl
 * @extends Zend_Acl
 */
class Helper_Acl extends Zend_Acl{
    
    /**
     * @brief Constructor that sets up the ACL configuration.
     * 
     * Defines roles (GUEST, ADMIN), resources (index, orders, administration),
     * and permissions that determine which roles can access which resources.
     * The hierarchy is set up so that ADMIN inherits all GUEST permissions.
     *
     * @return void
     */
    public function __construct(){

        // Define role constants
        define('GUEST', "GUEST");
        //define('USER', null);
        define('ADMIN', "ADMIN");

        // Add roles to ACL with inheritance (ADMIN inherits GUEST permissions)
        $this->addRole(new Zend_Acl_Role('GUEST'));
        //$this->addRole(new Zend_Acl_Role('USER'),'GUEST');
        $this->addRole(new Zend_Acl_Role('ADMIN'),'GUEST');

        // Add resources to ACL (controllers)
        //$this->addResource('default');
        $this->addResource('index');
        $this->addResource('orders');
        $this->addResource('administration');

        // Set up permissions
        // GUEST can access index and orders
        $this->allow(GUEST, 'index');
        $this->allow(GUEST, 'orders');
        
        // ADMIN can access administration
        $this->allow(ADMIN, 'administration');
    }
}