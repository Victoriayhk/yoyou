#coding: utf-8

import sys
import datetime
import time
import urllib2

from database_maintain import connect_database
from database_maintain import get_unsend_mails
from database_maintain import update_unsend_mails


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


def test(argv):
    url = "http://www.baidu.com/img/bd_logo1.png"
    img_content = urllib2.urlopen(url).read()

    file = open('baidu.png', 'w')
    file.write(img_content)
    file.close()
	# smtp_password = argv[0]

 #    db, cursor = connect_database()
 #    logger.info("connect to database succeed.")

 #    cur_time = get_cur_time()
 #    mails = get_unsend_mails(cursor, cur_time)
 #    print cur_time, mails

 #    cur_time = 1533922511
 #    mails = get_unsend_mails(cursor, cur_time)
 #    print get_cur_time(), mails
 #    update_unsend_mails(cursor, mails, cur_time, smtp_password)

 #    disconnect_database(db)
 #    logger.info("disconnect database")


if __name__ == "__main__":
    test(sys.argv[1:])