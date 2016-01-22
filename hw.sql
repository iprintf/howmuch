CREATE DATABASE /*!32312 IF NOT EXISTS*/ `howmuch` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `howmuch`;

CREATE TABLE `goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` char(20) NOT NULL,
  `name` char(50) NOT NULL,
  `label` char(50) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `unit` char(4) DEFAULT NULL,
  `merchant` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `kyo_session` (
  `session_id` varchar(255) NOT NULL,
  `session_expire` int(11) NOT NULL,
  `session_data` blob,
  UNIQUE KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `state` enum('1','2','3') NOT NULL,
  `attender` varchar(500) NOT NULL,
  `comment` varchar(500) DEFAULT NULL,
  `create_time` datetime NOT NULL,
  `finish_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `transaction_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tid` int(11) NOT NULL,
  `gid` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `quantity` decimal(5,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `owner` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tid` (`tid`),
  KEY `transaction_detail` (`gid`),
  CONSTRAINT `transaction_detail_ibfk_1` FOREIGN KEY (`tid`) REFERENCES `transaction` (`id`),
  CONSTRAINT `transaction_detail_ibfk_2` FOREIGN KEY (`gid`) REFERENCES `goods` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(30) NOT NULL,
  `sex` enum('1','2') DEFAULT NULL,
  `cellphone` char(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `payment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tid` (`tid`),
  CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`tid`) REFERENCES `transaction` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `balance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tid` (`tid`),
  CONSTRAINT `balance_ibfk_1` FOREIGN KEY (`tid`) REFERENCES `transaction` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


drop procedure IF EXISTS update_balance;
drop function IF EXISTS count_users;
drop function IF EXISTS calc_balance;
drop function IF EXISTS calc_share;
drop function IF EXISTS calc_pay;

delimiter //

-- update the balance table
create procedure update_balance(in input_tid int)
begin
    update balance set amount=calc_balance(tid, uid) where tid=input_tid;
end //


-- calculate the balance of a given transaction and user
create function calc_balance(input_tid int, input_uid int)
returns decimal(10,2)
begin
    select calc_pay(input_tid, input_uid) - calc_share(input_tid, input_uid) into @balance;
    return @balance;
end //

-- 算出给定的一笔交易明细中所有的参与者的数量
create function count_users(owner char(128))
returns int unsigned
begin
    set @str    = trim(leading ',' from owner);
    set @oldlen = length(@str);
    set @newlen = length(replace(@str, ',', ''));
    set @total  = @oldlen - @newlen;
    return @total;
end //

-- 算出给定的一笔交易中指定用户应付的金额
create function calc_share(input_tid int, input_uid int)
returns decimal(10,2) unsigned
begin
    select sum(total/count_users(owner)) into @share from transaction_detail where tid=input_tid and owner like concat('%,', input_uid, ',%');
    return @share;
end //

-- 算出给定的一笔交易中指定用户已付的金额
create function calc_pay(input_tid int, input_uid int)
returns decimal(10,2) unsigned
begin
    select sum(amount) into @pay from payment where tid=input_tid and uid=input_uid;
    if @pay is NULL then
        set @pay = 0;
    end if;
    return @pay;
end //

delimiter ;
