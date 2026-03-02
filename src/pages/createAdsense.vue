<template lang="pug">
v-card(
  style="width: 100%; height: 100%;"
  :class="settings.hidden.isAndroid15OrHigher ? 'top-android-15-or-higher' : ''"
  )
  v-card-actions
    p.ml-2(style="font-size: 1.3em") 新規広告の作成
    v-spacer
    v-btn(
      text
      @click="cancelDialog = true"
      append-icon="mdi-close"
    ) キャンセル
    v-btn(
      text
      @click="save()"
      append-icon="mdi-content-save"
      style="background-color: rgb(var(--v-theme-primary)); color: white;"
      ) 作成
  v-card-text(style="height: inherit; overflow-y: auto;")
    p 広告画像（4枚まで挿入できます）
    p 広告画像は、1秒ごとに切り替わります。
    .imgs
      .cover.my-4(
        v-for="content in form.contents"
        :key="content"
      )
        img.cover-img(
          :src="content"
          onerror="this.src='/img/default_cover.jpg'"
          )
        .change-cover-button(
          style="font-size: 2em;"
          v-ripple
          @click="changeCover()"
          )
          v-icon(
            style="opacity: 0.7;"
          ) mdi-camera-flip
      .cover.my-4(
        v-if="form.contents.length < 4"
      )
        .cover-img(
          )
        .change-cover-button(
          style="font-size: 2em;"
          v-ripple
          @click="form.contents.push('')"
          )
          v-icon(
            style="opacity: 0.7;"
          ) mdi-camera-flip
    .text-form
      v-text-field(
        name="広告タイトル"
        label="広告タイトル"
        placeholder="新しい広告"
        v-model="form.title"
      )
      v-textarea(
        label="広告の説明文"
        placeholder="広告の説明文を入力してください"
        clearable
        auto-grow
        v-model="form.description"
      )
      v-date-input(
        label="広告の開始日時"
        v-model="form.startDate"
      )
      v-date-input(
        label="広告の終了日時"
        v-model="form.endDate"
      )
v-dialog(
  v-model="cancelDialog"
  persistent
)
  v-card
    v-card-title キャンセル
    v-card-text 変更は保存されません。キャンセルしますか？
    v-card-actions
      v-btn(
        @click="cancelDialog = false"
      ) いいえ
      v-btn(
        @click="cancel()"
      ) はい（キャンセル）
v-dialog(
  v-model="saveDialog"
  persistent
)
  v-card(
    width="400"
  )
    v-card-text
      v-card-title 保存中
      v-progress-linear(
        indeterminate
      )
</template>

<script lang="ts">
  import type { Adsense } from '@/stores/adsense'
  import { App } from '@capacitor/app'

  import { Camera, CameraResultType, CameraSource } from '@capacitor/camera'
  import { VDateInput } from 'vuetify/labs/VDateInput'
  import mixins from '@/mixins/mixins'
  import { useMyProfileStore } from '@/stores/myProfile'
  import { useSettingsStore } from '@/stores/settings'

  export default {
    components: {
      VDateInput,
    },
    mixins: [mixins],
    data () {
      return {
        myProfile: useMyProfileStore(),
        cancelDialog: false,
        saveDialog: false,
        settings: useSettingsStore(),
        form: {
          adsenseId: '',
          authorUserId: '',
          title: '',
          description: '',
          startDate: new Date(),
          endDate: new Date(),
          jumpUrl: '',
          contents: [] as string[],
        } as Adsense,
      }
    },
    async mounted () {
      App.addListener('backButton', () => {
        this.cancelDialog = this.cancelDialog ? false : true
      })
      this.form.authorUserId = this.myProfile.userId
    },
    unmounted () {
      App.removeAllListeners()
    },
    methods: {
      /** 変更したプロフィールの保存 */
      async save () {
        this.saveDialog = true

        try {
          const res = await this.sendAjaxWithAuth('/createAdsense.php', {
            id: this.myProfile?.userId,
            token: this.myProfile?.userToken,
          }, {
            icon: this.myProfile?.icon,
            coverImg: this.myProfile?.coverImg,
            name: this.myProfile?.name,
            message: this.myProfile?.message,
          })
          let iconUrl = null
          let coverImgUrl = null
          if (res && res.body) {
            iconUrl = res.body.iconUrl
            coverImgUrl = res.body.coverImgUrl
          }

          /** iconとcoverImgはURLを格納する */
          this.myProfile = {
            ...this.myProfile,
            icon: iconUrl,
            coverImg: coverImgUrl,
          }
          const textProfile = JSON.stringify(this.myProfile)
          localStorage.setItem('profile', textProfile)
        } catch (error) {
          if (this.settings.developerOptions.enabled) {
            alert(error)
          }
        }
        this.saveDialog = false
        this.$router.back()
      },
      /** カバー画像の変更 */
      async changeCover () {
        const permission = await Camera.checkPermissions()
        if (permission.camera !== 'granted' || permission.photos !== 'granted') {
          await Camera.requestPermissions()
        }
        const image = await Camera.getPhoto({
          quality: 100,
          resultType: CameraResultType.DataUrl,
          allowEditing: false,
          saveToGallery: false,
          width: 1600,
          height: 1600,
          source: CameraSource.Photos,
          promptLabelHeader: '写真を使う',
          promptLabelCancel: 'キャンセル',
          promptLabelPhoto: 'アルバムから選択',
          promptLabelPicture: '撮影',
        })
        const base64 = image.dataUrl
        if (this.myProfile) {
          this.myProfile.coverImg = base64 ?? null
        }
      },
      /** アイコンの変更 */
      async changeIcon () {
        const permission = await Camera.checkPermissions()
        if (permission.camera !== 'granted' || permission.photos !== 'granted') {
          await Camera.requestPermissions()
        }
        const image = await Camera.getPhoto({
          quality: 100,
          resultType: CameraResultType.DataUrl,
          allowEditing: false,
          saveToGallery: false,
          width: 1600,
          height: 1600,
          source: CameraSource.Photos,
          promptLabelHeader: '写真を使う',
          promptLabelCancel: 'キャンセル',
          promptLabelPhoto: 'アルバムから選択',
          promptLabelPicture: '撮影',
        })
        const base64 = image.dataUrl
        if (this.myProfile) {
          this.myProfile.icon = base64 ?? null
        }
      },
      /** プロフィール編集をキャンセル */
      cancel () {
        // これないとバグる
        this.cancelDialog = false
        this.$router.back()
      },
    },
  }
</script>

<style lang="scss" scoped>
.top-android-15-or-higher {
  height: calc(100vh - 40px - 16px)!important;
}
.cover {
  width: 100%;
  position: relative;
  border-radius: 16px;
  overflow: hidden;
  .cover-img {
    width: 100%;
    aspect-ratio: 45/9;
  }
  .change-cover-button {
    position: absolute;
    top:50%;
    left:50%;
    transform: translate(-50%,-50%);
    padding: 0;
    margin: 0;
    background: rgba(0,0,0,0.5);
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    color: #FFFFFF;
  }
}
</style>
