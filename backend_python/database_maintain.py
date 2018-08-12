#coding: utf-8

"""
扫描数据库内的mail脚本, 并:

1. 对正在途中的mail的状态进行更新
2. 对到达指定寄达时间的mail进行发送并更新状态
"""
import sys

import MySQLdb
import json
import datetime
import time
import sched
import logging
import logging.handlers

import settings
from email_helper import EmailHandler

# 发送信的最小时间单位粒度, 比如最小粒度是天, 那么系统每隔一天扫描一次未发送的信, 
# 到了送信时间就送出去
# 单位: 秒
MINIMUM_TIME_GRANULARITY = 10

DB_config = {
    "host": "127.0.0.1",
    "user": "root",
    "passwd": "",
    "db": "manji",
    "charset": "utf8"
}

DB_attri = {
    "table_name": ["t_mail", "t_mail_state"],
    "t_mail": ["mail_id", "user_id", "address_id", "pub_time", "is_read", "arrive_time", "mail_content", "email"],
    "t_mail_state": [ "mstate_id", "poster_id", "start_time", "end_time", "description", "mood", "mood_time", "mail_id"],
}

# create logger
logger = logging.getLogger(__name__)
logger.setLevel(logging.DEBUG)

# create console handler and set level to debug
ch = logging.StreamHandler()
ch.setLevel(logging.DEBUG)

# create formatter
formatter = logging.Formatter('[%(asctime)s][%(levelname)s][%(name)s]: %(message)s')

# add formatter to ch
ch.setFormatter(formatter)

# add ch to logger
logger.addHandler(ch)

# ==========================================================


def connect_database():
    """连接数据库"""
    db = MySQLdb.connect(**DB_config)
    cursor = db.cursor()
    return db, cursor


def disconnect_database(db):
    """关闭数据库连接"""
    db.close()


def get_cur_time():
    return int(round(time.mktime(datetime.datetime.now().timetuple())))


def insert_a_fake_data(db, cursor):
    # 用户
    user_name = "julesyi"
    vuid = "huikangy"
    email = "huikangyi@gmail.com"
    user_img = "www.baidu.com/img/bd_logo1.png"
    sql_user = """INSERT INTO t_user(user_name, vuid, email, user_img) VALUES(\"{}\", \"{}\", \"{}\", \"{}\")""".format(user_name, vuid, email, user_img)
    cursor.execute(sql_user)

    # 地址
    addr = "testShenzhen"
    sql_address = """INSERT INTO t_address(addr) VALUES (\"{}\")""".format(addr)
    cursor.execute(sql_address)

    # 信使
    poster_url = "www.baidu.com/img/bd_logo1.png"
    poster_name = "pony"
    poster_desc = "test 1分钟左右送达"
    expect_time = 60
    sql_poster = """INSERT INTO t_poster(poster_name, poster_url, poster_desc, expect_time) VALUES(\"{}\",\"{}\", \"{}\", {})""".format(poster_name, poster_url, poster_desc, expect_time)
    cursor.execute(sql_poster)

    # 主信内容
    user_id = 1
    address_id = 1
    pub_time = int(round(time.mktime(datetime.datetime.now().timetuple())))
    is_read = 0
    arrive_time = pub_time + 10
    mail_content = "test <h2>hello world</h2>"
    email = "452570607@qq.com"
    sql_mail = """INSERT INTO t_mail(user_id, address_id, pub_time, is_read, arrive_time, mail_content, email)
    VALUES({}, {}, {}, {}, {}, \"{}\", \"{}\")""".format(user_id, address_id, pub_time, is_read, arrive_time, mail_content, email)
    cursor.execute(sql_mail)

    # 信的状态 1
    mail_id = 1
    start_time = pub_time
    end_time = pub_time + 4
    # mood = "test: I'm in a mood"
    # mood_time = start_time + 60 * 10000
    description = "信使遇到了暴雨, 可能会迟到"
    sql_mail_state_1 = """INSERT INTO t_mail_state(mail_id, start_time, end_time, description) VALUES({}, {}, {}, \"{}\")""".format(mail_id, start_time, end_time, description)
    cursor.execute(sql_mail_state_1)

    # 信的状态 2
    mail_id = 1
    start_time = end_time + 1
    end_time = arrive_time
    # mood = "test: I'm in a mood"
    # mood_time = start_time + 60 * 10000
    description = "信使遇到了初恋, 跑起来就像一阵风"
    sql_mail_state_2 = """INSERT INTO t_mail_state(mail_id, start_time, end_time, description) VALUES({}, {}, {}, \"{}\")""".format(mail_id, start_time, end_time, description)
    cursor.execute(sql_mail_state_2)

    # db.commit()
    return 0


def update_test_data(db, cursor):
    # 信的到达时间
    pub_time = int(round(time.mktime(datetime.datetime.now().timetuple())))
    arrive_time = pub_time + 10
    cursor.execute("""UPDATE t_mail SET pub_time={}, arrive_time={} WHERE mail_id={}""".format(pub_time, arrive_time, 2))

    # 信的状态 1
    start_time = pub_time
    end_time = pub_time + 4
    cursor.execute("""UPDATE t_mail_state SET start_time={}, end_time={} WHERE mstate_id={}""".format(start_time, end_time, 3))

    # 信的状态 2
    start_time = end_time + 1
    end_time = arrive_time
    cursor.execute("""UPDATE t_mail_state SET start_time={}, end_time={} WHERE mstate_id={}""".format(start_time, end_time, 4))

    db.commit()
    return 0


def get_unsend_mails(cursor, cur_time):
    """获取所有未发送的mail"""
    # cur_time = int(round(time.mktime(datetime.datetime.now().timetuple())))
    sql = """SELECT t_mail.mail_id as mail_id, t_mail.arrive_time as arrive_time FROM t_mail WHERE {} < t_mail.arrive_time""".format(cur_time)
    cursor.execute(sql)
    mails = cursor.fetchall()
    return mails


def form_email_content(mail_content, mail_states):
    text = ""
    html = mail_content
    image_url_list = []
    return text, html, image_url_list


def parse_mail(cursor, mail_id):
    # 获取mail信息
    sql = """SELECT * FROM t_mail WHERE mail_id={}""".format(mail_id)
    cursor.execute(sql)
    mail = cursor.fetchall()[0]
    logger.info("parse mail: {}".format(mail))

    # 获取mail的状态(们), 并按时间顺序排序
    sql = """SELECT * FROM t_mail_state WHERE mail_id={} ORDER BY field(start_time) DESC""".format(mail_id)
    cursor.execute(sql)
    mail_states = cursor.fetchall()
    logger.info("parse state: {}".format(mail_states))

    email_to = [mail[5]]
    subject = settings.standard_email_subject
    text, html, image_url_list = form_email_content(mail[6], mail_states)
    return email_to, subject, text, html, image_url_list


def update_unsend_mails(cursor, mails, cur_time, smtp_password):
    num_send_out = 0

    """更新未发送mail的状态"""
    for mail in mails:
        mail_id = mail[0]
        arrive_time = mail[1]
        if (arrive_time + MINIMUM_TIME_GRANULARITY >= cur_time):
            """到了需要送信的时间"""
            email_to, subject, text, html, image_url_list = parse_mail(cursor, mail_id)
            # mail寄达
            try:
                email_sender = EmailHandler(
                    settings.smtp_server, settings.smtp_username,
                    smtp.password)
                email_sender.send_email(
                    settings.smtp_username, email_to,
                    subject, text,
                    html, image_url_list)

                num_send_out += 1
            except Exception as e:
                logging.error("{} send mail failed. mail_id={}, mail_to=[{}]".format(
                    str(e), mail_id, ", ".join(email_to)))

            logging.info("sending out {} mail(s).".format(num_send_out))


def maintain_mails(smtp_password):
    try:
        db, cursor = connect_database()
        logger.info("connect to database succeed.")

        cur_time = get_cur_time()
        mails = get_unsend_mails(cursor, cur_time)
        update_unsend_mails(cursor, mails, cur_time, smtp_password)

        disconnect_database(db)
        logger.info("disconnect with database.")
    except Exception as e:
        logger.error("maintain mails failed. {}".format(str(e)))


def main(argv):
    smtp_password = argv[0]
    while (True):
        logger.info("starting scan mails...")
        maintain_mails(smtp_password)
        time.sleep(MINIMUM_TIME_GRANULARITY)


def test():
    db, cursor = connect_database()
    logger.info("connect to database succeed.")

    cur_time = get_cur_time()
    mails = get_unsend_mails(cursor, cur_time)
    print cur_time, mails

    cur_time = 1533922511
    mails = get_unsend_mails(cursor, cur_time)
    print get_cur_time(), mails
    update_unsend_mails(cursor, mails, cur_time)

    disconnect_database(db)
    logger.info("disconnect database")


if __name__ == "__main__":
    # test()
    main(sys.argv[1:])
