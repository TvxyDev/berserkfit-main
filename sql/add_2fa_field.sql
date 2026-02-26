-- Script para adicionar suporte a 2FA na tabela user
USE `berserkfit`;

-- Adicionar campo tfa_secret à tabela user
ALTER TABLE `user` 
ADD COLUMN `tfa_secret` VARCHAR(32) NULL DEFAULT NULL AFTER `username`;

-- Comentário: Este campo armazenará o segredo de autenticação de dois fatores (2FA) do utilizador
-- Se for NULL, o 2FA está desativado. Se tiver um valor, o 2FA está ativo.
