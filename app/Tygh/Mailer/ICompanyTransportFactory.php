<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/


namespace Tygh\Mailer;

/**
 * The interface of the class responsible for the creation of transport object by company_id.
 * Needed for backward compatibility.
 * 
 * @package Tygh\Mailer
 */
interface ICompanyTransportFactory
{
    /**
     * Create transport instance by company identifier
     *
     * @param   int    $company_id  Сompany identifier
     *
     * @return ITransport
     */
    public function createTransportByCompanyId($company_id);
}