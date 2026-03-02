import { defineStore } from 'pinia'

export type Adsense = {
  /** 広告のID */
  adsenseId: string
  /** 投稿者のユーザーID */
  authorUserId: string
  /** 広告のタイトル */
  title: string | null
  /** 広告の説明文（非公開） */
  description: string | null
  /** 広告で表示する画像リスト */
  contents: string[]
  /** 推移先URL */
  jumpUrl: string
  /** 広告の開始日 */
  startDate: Date
  /** 広告の終了日 */
  endDate: Date
}

export const useAdsenseStore = defineStore('myAdsense', {
  state: () => ({
    myAdsenses: [] as Adsense[],
  }),
  actions: {},
  persist: true,
})
