<?php

namespace LgtToolkit\Tests\Unit;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

/**
 * In-memory SQLite copy of the two queue tables (mirrors the entities in src/Concrete/Entity).
 */
trait QueueSchema
{
    private function queueDatabase(): Connection
    {
        $db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $db->executeStatement('PRAGMA foreign_keys = ON');
        $db->executeStatement('CREATE TABLE LgtSupportTicketQueue (
            id CHAR(36) PRIMARY KEY, date_created DATETIME NOT NULL, date_updated DATETIME NOT NULL,
            localReference VARCHAR(20) NOT NULL UNIQUE, email VARCHAR(255) NOT NULL, name VARCHAR(100), subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL, pageUrl VARCHAR(255), uID INTEGER, status VARCHAR(12) NOT NULL, attempts SMALLINT NOT NULL,
            nextAttemptAt DATETIME NOT NULL, lastAttemptAt DATETIME, lockedUntil DATETIME, lastStatusCode SMALLINT, lastError TEXT,
            possibleDuplicate BOOLEAN NOT NULL, configError BOOLEAN NOT NULL, portalTicket INTEGER)');
        $db->executeStatement('CREATE TABLE LgtSupportTicketQueueAttachments (
            id CHAR(36) PRIMARY KEY, queueItemID CHAR(36) NOT NULL REFERENCES LgtSupportTicketQueue(id) ON DELETE CASCADE,
            filename VARCHAR(100) NOT NULL, mime VARCHAR(50) NOT NULL, size INTEGER NOT NULL, content BLOB NOT NULL)');
        $db->executeStatement('CREATE TABLE LgtSupportTickets (
            id CHAR(36) PRIMARY KEY, date_created DATETIME NOT NULL, date_updated DATETIME NOT NULL,
            localReference VARCHAR(20) NOT NULL UNIQUE, category VARCHAR(20) NOT NULL, email VARCHAR(255) NOT NULL, name VARCHAR(100),
            uID INTEGER, subject VARCHAR(200) NOT NULL, message TEXT NOT NULL, pageUrl VARCHAR(255), status VARCHAR(12) NOT NULL,
            portalTicket INTEGER, portalReference VARCHAR(50), portalUrl VARCHAR(255), deliveredAt DATETIME, lastError TEXT)');

        return $db;
    }
}
