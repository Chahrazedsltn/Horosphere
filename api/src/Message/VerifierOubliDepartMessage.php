<?php

namespace App\Message;

/**
 * Message dispatché chaque soir à 23h30 par le Symfony Scheduler
 * pour détecter les pointages EN_COURS non clôturés.
 */
final class VerifierOubliDepartMessage {}
